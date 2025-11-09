<?php

namespace App\SourceControlProviders;

use App\Exceptions\FailedToDeployGitHook;
use App\Exceptions\FailedToDeployGitKey;
use App\Exceptions\FailedToDestroyGitHook;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class Gitea extends AbstractSourceControlProvider
{
    protected string $defaultApiHost = 'https://gitea.com/';

    protected string $apiVersion = 'api/v1';

    private const int CACHE_TTL = 60 * 15; // 15 minutes

    private const int MAX_LIMIT = 50; // Gitea's max limit per page

    private const int MAX_PAGES = 25; // Safety limit

    public static function id(): string
    {
        return 'gitea';
    }

    public function createRules(array $input): array
    {
        return [
            'token' => 'required',
            'url' => [
                'nullable',
                'url:http,https',
                'ends_with:/',
            ],
        ];
    }

    public function connect(): bool
    {
        try {
            $res = Http::withToken($this->data()['token'])
                ->get($this->getApiUrl().'/repos/search');
        } catch (Exception) {
            return false;
        }

        return $res->successful();
    }

    /**
     * @throws Exception
     */
    public function getRepo(string $repo): mixed
    {
        $res = Http::withToken($this->data()['token'])
            ->get($this->getApiUrl().'/repos/'.$repo);

        $this->handleResponseErrors($res, $repo);

        return $res->json();
    }

    public function fullRepoUrl(string $repo, string $key): string
    {
        $host = parse_url($this->getApiUrl())['host'] ?? 'gitea.com';

        return sprintf('git@%s-%s:%s.git', $host, $key, $repo);
    }

    /**
     * @throws FailedToDeployGitHook
     */
    public function deployHook(string $repo, array $events, string $secret): array
    {
        try {
            $response = Http::withToken($this->data()['token'])->post(
                $this->getApiUrl().'/repos/'.$repo.'/hooks',
                [
                    'active' => true,
                    'events' => $events,
                    'type' => 'gitea',
                    'config' => [
                        'url' => url('/api/git-hooks?secret='.$secret),
                        'content_type' => 'json',
                        'insecure_ssl' => '0',
                        'secret' => $secret,
                    ],
                    'authorization_header' => $secret,
                ]
            );
        } catch (Exception $e) {
            throw new FailedToDeployGitHook($e->getMessage());
        }

        if ($response->status() != 201) {
            throw new FailedToDeployGitHook($response->body());
        }

        return [
            'hook_id' => json_decode($response->body())->id,
            'hook_response' => json_decode($response->body()),
        ];
    }

    /**
     * @throws FailedToDestroyGitHook
     */
    public function destroyHook(string $repo, string $hookId): void
    {
        try {
            $response = Http::withToken($this->data()['token'])->delete(
                $this->getApiUrl().'/repos/'.$repo.'/hooks/'.$hookId
            );
        } catch (Exception $e) {
            throw new FailedToDestroyGitHook($e->getMessage());
        }

        if ($response->status() != 204) {
            throw new FailedToDestroyGitHook($response->body());
        }
    }

    /**
     * @throws Exception
     */
    public function getLastCommit(string $repo, string $branch): ?array
    {
        $res = Http::withToken($this->data()['token'])
            ->get($this->getApiUrl().'/repos/'.$repo.'/commits?sha='.$branch);

        $this->handleResponseErrors($res, $repo);

        $commits = $res->json();
        if (count($commits) > 0) {
            return [
                'commit_id' => $commits[0]['sha'],
                'commit_data' => [
                    'name' => $commits[0]['commit']['committer']['name'] ?? null,
                    'email' => $commits[0]['commit']['committer']['email'] ?? null,
                    'message' => $commits[0]['commit']['message'] ?? null,
                    'url' => $commits[0]['commit']['url'] ?? null,
                ],
            ];
        }

        return null;
    }

    /**
     * @throws FailedToDeployGitKey
     */
    public function deployKey(string $title, string $repo, string $key): string
    {
        try {
            $response = Http::withToken($this->data()['token'])->post(
                $this->getApiUrl().'/repos/'.$repo.'/keys',
                [
                    'title' => $title,
                    'key' => $key,
                    'read_only' => true,
                ]
            );

            if ($response->status() != 201) {
                throw new FailedToDeployGitKey($response->body());
            }

            return $response->json()['id'] ?? '';
        } catch (Exception $e) {
            throw new FailedToDeployGitKey($e->getMessage());
        }
    }

    public function deleteDeployKey(string $keyId, string $repo): void
    {
        try {
            $response = Http::withToken($this->data()['token'])->delete(
                $this->getApiUrl().'/repos/'.$repo.'/keys/'.$keyId
            );

            if (! $response->successful()) {
                Log::warning('Failed to delete Gitea deploy key', [
                    'repo' => $repo,
                    'key_id' => $keyId,
                    'response' => $response->body(),
                ]);
            }

        } catch (Throwable $e) {
            Log::error('Error deleting Gitea deploy key', [
                'repo' => $repo,
                'key_id' => $keyId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function getApiUrl(): string
    {
        $host = $this->sourceControl->url ?? $this->defaultApiHost;

        return $host.$this->apiVersion;
    }

    public function getRepos(bool $useCache = true): array
    {
        $cacheKey = 'gitea_repos_'.md5($this->getApiUrl().$this->data()['token']);

        if ($useCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $repos = $this->fetchAllPages('/user/repos', [
                'limit' => self::MAX_LIMIT,
            ]);

            $repoNames = $repos->pluck('full_name')->toArray();
            Cache::put($cacheKey, $repoNames, self::CACHE_TTL);

            return $repoNames;

        } catch (Throwable $e) {
            Log::error('Failed to fetch Gitea repositories', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    public function getBranches(string $repo, bool $useCache = true): array
    {
        $cacheKey = 'gitea_branches_'.md5($repo.$this->getApiUrl().$this->data()['token']);

        if ($useCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $branches = $this->fetchAllPages("/repos/$repo/branches", [
                'limit' => self::MAX_LIMIT,
            ]);

            $branchNames = $branches->pluck('name')->toArray();
            Cache::put($cacheKey, $branchNames, self::CACHE_TTL);

            return $branchNames;

        } catch (Throwable $e) {
            Log::error('Failed to fetch Gitea branches', [
                'repo' => $repo,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Fetch all pages from Gitea API
     * Gitea uses pagination with 'page' and 'limit' parameters
     *
     * @param  string  $endpoint  API endpoint (without base URL)
     * @param  array<string, mixed>  $params  Query parameters
     * @return Collection<int, mixed>
     */
    private function fetchAllPages(string $endpoint, array $params = []): Collection
    {
        $allData = collect();
        $page = 1;
        $hasMore = true;

        while ($hasMore) {
            $params['page'] = $page;
            $response = Http::withToken($this->data()['token'])
                ->get($this->getApiUrl().$endpoint, $params);

            if (! $response->successful()) {
                Log::error('Gitea API request failed', [
                    'endpoint' => $endpoint,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                break;
            }

            $pageData = $response->json();
            if (empty($pageData)) {
                $hasMore = false;
            } else {
                $allData = $allData->concat($pageData);

                // Gitea pagination: if we got fewer items than the limit, we're done
                $limit = $params['limit'] ?? self::MAX_LIMIT;
                $hasMore = count($pageData) >= $limit;
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
