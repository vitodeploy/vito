<?php

namespace App\SourceControlProviders;

use App\Exceptions\FailedToDeployGitHook;
use App\Exceptions\FailedToDeployGitKey;
use App\Exceptions\FailedToDestroyGitHook;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class Github extends AbstractSourceControlProvider
{
    private const string API_BASE_URL = 'https://api.github.com';

    private const int CACHE_TTL = 60 * 15;

    private const int MAX_PER_PAGE = 100;

    private const int MAX_PAGES = 25;

    public static function id(): string
    {
        return 'github';
    }

    private function getClient(): PendingRequest
    {
        return Http::withHeaders([
            'Accept' => 'application/vnd.github.v3+json',
            'Authorization' => 'Bearer '.$this->data()['token'],
        ]);
    }

    public function connect(): bool
    {
        try {
            $res = $this->getClient()
                ->get(self::API_BASE_URL.'/user');

            return $res->successful();
        } catch (Exception $e) {
            Log::error('GitHub connection failed', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * @throws Exception
     */
    public function getRepo(string $repo): mixed
    {
        $url = $repo !== '' && $repo !== '0'
            ? self::API_BASE_URL.'/repos/'.$repo
            : self::API_BASE_URL.'/user/repos';

        $res = $this->getClient()->get($url);

        $this->handleResponseErrors($res, $repo);

        return $res->json();
    }

    public function fullRepoUrl(string $repo, string $key): string
    {
        return sprintf('git@github.com-%s:%s.git', $key, $repo);
    }

    /**
     * @throws FailedToDeployGitHook
     */
    public function deployHook(string $repo, array $events, string $secret): array
    {
        try {
            $response = $this->getClient()
                ->post(self::API_BASE_URL."/repos/$repo/hooks", [
                    'name' => 'web',
                    'events' => $events,
                    'config' => [
                        'url' => url('/api/git-hooks?secret='.$secret),
                        'content_type' => 'json',
                    ],
                    'active' => true,
                ]);

            if ($response->status() !== 201) {
                throw new FailedToDeployGitHook($response->body());
            }

            $hookData = $response->json();

            return [
                'hook_id' => $hookData['id'],
                'hook_response' => $hookData,
            ];

        } catch (Throwable $e) {
            Log::error('Failed to deploy GitHub hook', [
                'repo' => $repo,
                'error' => $e->getMessage(),
            ]);

            throw new FailedToDeployGitHook($e->getMessage());
        }
    }

    /**
     * @throws FailedToDestroyGitHook
     */
    public function destroyHook(string $repo, string $hookId): void
    {
        try {
            $response = $this->getClient()
                ->delete(self::API_BASE_URL."/repos/$repo/hooks/$hookId");

            if ($response->status() !== 204) {
                throw new FailedToDestroyGitHook($response->body());
            }

        } catch (Throwable $e) {
            Log::error('Failed to destroy GitHub hook', [
                'repo' => $repo,
                'hook_id' => $hookId,
                'error' => $e->getMessage(),
            ]);

            throw new FailedToDestroyGitHook($e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public function getLastCommit(string $repo, string $branch): ?array
    {
        $cacheKey = 'github_commit_'.md5($repo.$branch.$this->data()['token']);

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $url = self::API_BASE_URL.'/repos/'.$repo.'/commits/'.$branch;
            $res = $this->getClient()->get($url);
            $this->handleResponseErrors($res, $repo);

            $commit = $res->json();

            if (isset($commit['sha']) && isset($commit['commit'])) {
                $result = [
                    'commit_id' => $commit['sha'],
                    'commit_data' => [
                        'name' => $commit['commit']['committer']['name'] ?? null,
                        'email' => $commit['commit']['committer']['email'] ?? null,
                        'message' => $commit['commit']['message'] ?? null,
                        'url' => $commit['html_url'] ?? null,
                    ],
                ];

                Cache::put($cacheKey, $result, 60); // Cache for 1 minute

                return $result;
            }

            return null;

        } catch (Throwable $e) {
            Log::error('Failed to fetch last commit', [
                'repo' => $repo,
                'branch' => $branch,
                'error' => $e->getMessage(),
            ]);

            throw new Exception('Failed to fetch last commit: '.$e->getMessage());
        }
    }

    /**
     * @throws FailedToDeployGitKey
     */
    public function deployKey(string $title, string $repo, string $key): string
    {
        try {
            $response = $this->getClient()
                ->post(self::API_BASE_URL.'/repos/'.$repo.'/keys', [
                    'title' => $title,
                    'key' => $key,
                    'read_only' => false,
                ]);

            if ($response->status() !== 201) {
                throw new FailedToDeployGitKey($response->body());
            }

            return $response->json()['id'] ?? '';

        } catch (Throwable $e) {
            Log::error('Failed to deploy GitHub key', [
                'repo' => $repo,
                'title' => $title,
                'error' => $e->getMessage(),
            ]);

            throw new FailedToDeployGitKey($e->getMessage());
        }
    }

    public function deleteDeployKey(string $keyId, string $repo): void
    {
        try {
            $response = $this->getClient()
                ->delete(self::API_BASE_URL."/repos/$repo/keys/$keyId");

            if (! $response->successful()) {
                Log::warning('Failed to delete GitHub deploy key', [
                    'repo' => $repo,
                    'key_id' => $keyId,
                    'response' => $response->body(),
                ]);
            }

        } catch (Throwable $e) {
            Log::error('Error deleting GitHub deploy key', [
                'repo' => $repo,
                'key_id' => $keyId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function getRepos(bool $useCache = true): array
    {
        $cacheKey = 'github_repos_'.md5($this->data()['token']);
        if ($useCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $repos = $this->fetchAllPages('/user/repos', [
                'type' => 'all',
                'per_page' => self::MAX_PER_PAGE,
            ]);

            $repoNames = $repos->pluck('full_name')->toArray();
            Cache::put($cacheKey, $repoNames, self::CACHE_TTL);

            return $repoNames;

        } catch (Throwable $e) {
            Log::error('Failed to fetch GitHub repositories', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    public function getBranches(string $repo, bool $useCache = true): array
    {
        $cacheKey = 'github_branches_'.md5($repo.$this->data()['token']);
        if ($useCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $branches = $this->fetchAllPages("/repos/{$repo}/branches", [
                'per_page' => self::MAX_PER_PAGE,
            ]);

            $branchNames = $branches->pluck('name')->toArray();
            Cache::put($cacheKey, $branchNames, self::CACHE_TTL);

            return $branchNames;

        } catch (Throwable $e) {
            Log::error('Failed to fetch GitHub branches', [
                'repo' => $repo,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    private function fetchAllPages(string $endpoint, array $params = []): Collection
    {
        $allData = collect();
        $page = 1;
        $hasMore = true;

        while ($hasMore) {
            $params['page'] = $page;
            $response = $this->getClient()->get(
                self::API_BASE_URL.$endpoint,
                $params
            );

            if (! $response->successful()) {
                throw new RequestException($response);
            }

            $pageData = $response->json();
            if (empty($pageData)) {
                $hasMore = false;
            } else {
                $allData = $allData->concat($pageData);
                $linkHeader = $response->header('Link');
                $hasMore = $linkHeader && str_contains($linkHeader, 'rel="next"');

                if (count($pageData) < $params['per_page']) {
                    $hasMore = false;
                }

                $page++;
            }

            if ($page > self::MAX_PAGES) {
                Log::warning('Reached pagination limit', [
                    'endpoint' => $endpoint,
                    'pages_fetched' => $page - 1,
                ]);
                break;
            }
        }

        return $allData;
    }
}
