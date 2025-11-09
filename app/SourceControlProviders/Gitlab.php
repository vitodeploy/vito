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

class Gitlab extends AbstractSourceControlProvider
{
    protected string $defaultApiHost = 'https://gitlab.com/';

    protected string $apiVersion = 'api/v4';

    private const int CACHE_TTL = 60 * 15; // 15 minutes

    private const int MAX_PER_PAGE = 100; // GitLab's max per_page

    private const int MAX_PAGES = 25; // Safety limit

    public static function id(): string
    {
        return 'gitlab';
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
                ->get($this->getApiUrl().'/version');
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
        $repository = $repo !== '' && $repo !== '0' ? urlencode($repo) : null;
        $res = Http::withToken($this->data()['token'])
            ->get($this->getApiUrl().'/projects/'.$repository.'/repository/commits');

        $this->handleResponseErrors($res, $repo);

        return $res->json();
    }

    public function fullRepoUrl(string $repo, string $key): string
    {
        $host = parse_url($this->getApiUrl())['host'] ?? 'gitlab.com';

        return sprintf('git@%s-%s:%s.git', $host, $key, $repo);
    }

    /**
     * @throws FailedToDeployGitHook
     */
    public function deployHook(string $repo, array $events, string $secret): array
    {
        $repository = urlencode($repo);
        try {
            $response = Http::withToken($this->data()['token'])->post(
                $this->getApiUrl().'/projects/'.$repository.'/hooks',
                [
                    'description' => 'deploy',
                    'url' => url('/api/git-hooks?secret='.$secret),
                    'push_events' => in_array('push', $events),
                    'issues_events' => false,
                    'job_events' => false,
                    'merge_requests_events' => false,
                    'note_events' => false,
                    'pipeline_events' => false,
                    'tag_push_events' => false,
                    'wiki_page_events' => false,
                    'deployment_events' => false,
                    'confidential_note_events' => false,
                    'confidential_issues_events' => false,
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
        $repository = urlencode($repo);
        try {
            $response = Http::withToken($this->data()['token'])->delete(
                $this->getApiUrl().'/projects/'.$repository.'/hooks/'.$hookId
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
        $repository = urlencode($repo);
        $res = Http::withToken($this->data()['token'])
            ->get($this->getApiUrl().'/projects/'.$repository.'/repository/commits?ref_name='.$branch);

        $this->handleResponseErrors($res, $repo);

        $commits = $res->json();
        if (count($commits) > 0) {
            return [
                'commit_id' => $commits[0]['id'],
                'commit_data' => [
                    'name' => $commits[0]['committer_name'] ?? null,
                    'email' => $commits[0]['committer_email'] ?? null,
                    'message' => $commits[0]['title'] ?? null,
                    'url' => $commits[0]['web_url'] ?? null,
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
        $repository = urlencode($repo);
        try {
            $response = Http::withToken($this->data()['token'])->post(
                $this->getApiUrl().'/projects/'.$repository.'/deploy_keys',
                [
                    'title' => $title,
                    'key' => $key,
                    'can_push' => true,
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
            $repository = urlencode($repo);
            $response = Http::withToken($this->data()['token'])->delete(
                $this->getApiUrl().'/projects/'.$repository.'/deploy_keys/'.$keyId
            );

            if (! $response->successful()) {
                Log::warning('Failed to delete Gitlab deploy key', [
                    'repo' => $repo,
                    'key_id' => $keyId,
                    'response' => $response->body(),
                ]);
            }

        } catch (Throwable $e) {
            Log::error('Error deleting Gitlab deploy key', [
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
        $cacheKey = 'gitlab_repos_'.md5($this->getApiUrl().$this->data()['token']);

        if ($useCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $repos = $this->fetchAllPages('/projects', [
                'membership' => true, // Only repos where user is a member
                'per_page' => self::MAX_PER_PAGE,
            ]);

            $repoNames = $repos->pluck('path_with_namespace')->toArray();
            Cache::put($cacheKey, $repoNames, self::CACHE_TTL);

            return $repoNames;

        } catch (Throwable $e) {
            Log::error('Failed to fetch GitLab repositories', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    public function getBranches(string $repo, bool $useCache = true): array
    {
        $cacheKey = 'gitlab_branches_'.md5($repo.$this->getApiUrl().$this->data()['token']);

        if ($useCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $repository = urlencode($repo);
            $branches = $this->fetchAllPages("/projects/$repository/repository/branches", [
                'per_page' => self::MAX_PER_PAGE,
            ]);

            $branchNames = $branches->pluck('name')->toArray();
            Cache::put($cacheKey, $branchNames, self::CACHE_TTL);

            return $branchNames;

        } catch (Throwable $e) {
            Log::error('Failed to fetch GitLab branches', [
                'repo' => $repo,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Fetch all pages from GitLab API
     * GitLab uses pagination with 'page' parameter and Link headers
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
                Log::error('GitLab API request failed', [
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

                // GitLab pagination uses Link header or X-Total-Pages header
                $linkHeader = $response->header('Link');
                $totalPages = (int) $response->header('X-Total-Pages');

                if ($totalPages > 0) {
                    $hasMore = $page < $totalPages;
                } elseif ($linkHeader && str_contains($linkHeader, 'rel="next"')) {
                    $hasMore = true;
                } else {
                    // If we got fewer items than per_page, we've reached the end
                    $perPage = $params['per_page'] ?? self::MAX_PER_PAGE;
                    $hasMore = count($pageData) >= $perPage;
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
