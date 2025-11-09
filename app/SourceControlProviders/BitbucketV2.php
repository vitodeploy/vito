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
use Illuminate\Support\Str;
use Throwable;

class BitbucketV2 extends AbstractSourceControlProvider
{
    protected string $apiUrl = 'https://api.bitbucket.org/2.0';

    protected string $oauthTokenUrl = 'https://bitbucket.org/site/oauth2/access_token';

    private const int CACHE_TTL = 60 * 15; // 15 minutes

    private const int MAX_PAGELEN = 100; // Bitbucket's max pagelen

    private const int MAX_PAGES = 25; // Safety limit

    public static function id(): string
    {
        return 'bitbucket-v2';
    }

    public function createRules(array $input): array
    {
        return [
            'key' => 'required',
            'secret' => 'required',
        ];
    }

    public function createData(array $input): array
    {
        return [
            'key' => $input['key'] ?? '',
            'secret' => $input['secret'] ?? '',
        ];
    }

    public function data(): array
    {
        return [
            'key' => $this->sourceControl->provider_data['key'] ?? '',
            'secret' => $this->sourceControl->provider_data['secret'] ?? '',
        ];
    }

    /**
     * Get access token for OAuth consumer
     * Uses client credentials grant for private OAuth consumers
     * Caches token for 10 minutes to avoid unnecessary API calls
     */
    private function getAccessToken(): ?string
    {
        $data = $this->data();
        $key = $data['key'];
        $secret = $data['secret'];
        $cacheKey = "bitbucket_v2_token_{$this->sourceControl->id}";

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($key, $secret) {
            return $this->getAccessTokenWithClientCredentials($key, $secret);
        });
    }

    /**
     * Get access token using client credentials grant
     * This works for private OAuth consumers only
     */
    private function getAccessTokenWithClientCredentials(string $key, string $secret): ?string
    {
        try {
            $response = Http::withBasicAuth($key, $secret)
                ->asForm()
                ->post($this->oauthTokenUrl, [
                    'grant_type' => 'client_credentials',
                ]);

            if ($response->successful()) {
                $tokenData = $response->json();

                return $tokenData['access_token'] ?? null;
            }

            $errorBody = $response->json();
            $errorMessage = $errorBody['error_description'] ?? $errorBody['error'] ?? $errorBody['message'] ?? $response->body();

            Log::error('Failed to get Bitbucket access token with client credentials', [
                'status' => $response->status(),
                'body' => $response->body(),
                'error' => $errorMessage,
            ]);

            throw new Exception($errorMessage);
        } catch (Exception $e) {
            Log::error('Error getting Bitbucket access token with client credentials', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * @throws Exception
     */
    public function connect(): bool
    {
        // Test the access token by making an API call
        $res = Http::withHeaders($this->getAuthenticationHeaders())
            ->get($this->apiUrl.'/user');

        if ($res->successful()) {
            return true;
        }

        $errorBody = $res->json();
        $errorMessage = $errorBody['error_description'] ?? $errorBody['error'] ?? $errorBody['message'] ?? $res->body();

        Log::error('Bitbucket V2 connection failed', [
            'status' => $res->status(),
            'body' => $res->body(),
            'error' => $errorMessage,
        ]);

        throw new Exception($errorMessage);
    }

    /**
     * @throws Exception
     */
    public function getRepo(string $repo): mixed
    {
        $res = Http::withHeaders($this->getAuthenticationHeaders())
            ->get($this->apiUrl."/repositories/$repo");

        $this->handleResponseErrors($res, $repo);

        return $res->json();
    }

    /**
     * Generate the full repository URL for Git operations
     *
     * @param  string  $repo  The repository identifier (e.g., workspace/repo)
     * @param  string  $key  The SSH key identifier
     * @return string The full Git URL
     */
    public function fullRepoUrl(string $repo, string $key): string
    {
        return sprintf('git@bitbucket.org-%s:%s.git', $key, $repo);
    }

    /**
     * @throws FailedToDeployGitHook
     */
    public function deployHook(string $repo, array $events, string $secret): array
    {
        try {
            $response = Http::withHeaders($this->getAuthenticationHeaders())
                ->post($this->apiUrl."/repositories/$repo/hooks", [
                    'description' => 'deploy',
                    'url' => url('/api/git-hooks?secret='.$secret),
                    'events' => array_map(fn ($event) => 'repo:'.$event, $events),
                    'active' => true,
                ]);

            if ($response->status() !== 201) {
                $errorBody = $response->json();
                $errorMessage = $errorBody['error']['message'] ?? $errorBody['error_description'] ?? $errorBody['error'] ?? $errorBody['message'] ?? $response->body();
                throw new FailedToDeployGitHook($errorMessage);
            }

            $hookData = $response->json();

            return [
                'hook_id' => $hookData['uuid'] ?? null,
                'hook_response' => $hookData,
            ];
        } catch (Exception $e) {
            if ($e instanceof FailedToDeployGitHook) {
                throw $e;
            }

            throw new FailedToDeployGitHook($e->getMessage());
        }
    }

    /**
     * @throws FailedToDestroyGitHook
     */
    public function destroyHook(string $repo, string $hookId): void
    {
        $hookId = urlencode($hookId);
        try {
            $response = Http::withHeaders($this->getAuthenticationHeaders())
                ->delete($this->apiUrl."/repositories/$repo/hooks/$hookId");
        } catch (Exception $e) {
            throw new FailedToDestroyGitHook($e->getMessage());
        }

        if ($response->status() !== 204) {
            $errorBody = $response->json();
            $errorMessage = $errorBody['error']['message'] ?? $errorBody['error_description'] ?? $errorBody['error'] ?? $errorBody['message'] ?? $response->body();
            throw new FailedToDestroyGitHook($errorMessage);
        }
    }

    /**
     * @throws Exception
     */
    public function getLastCommit(string $repo, string $branch): ?array
    {
        $res = Http::withHeaders($this->getAuthenticationHeaders())
            ->get($this->apiUrl."/repositories/$repo/commits?include=".$branch);

        $this->handleResponseErrors($res, $repo);

        $commits = $res->json();

        if (isset($commits['values']) && count($commits['values']) > 0) {
            $committer = $this->getCommitter($commits['values'][0]['author']['raw'] ?? '');

            return [
                'commit_id' => $commits['values'][0]['hash'],
                'commit_data' => [
                    'name' => $committer['name'] ?? null,
                    'email' => $committer['email'] ?? null,
                    'message' => str_replace("\n", '', $commits['values'][0]['message']),
                    'url' => $commits['values'][0]['links']['html']['href'] ?? null,
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
            $res = Http::withHeaders($this->getAuthenticationHeaders())->post(
                $this->apiUrl."/repositories/$repo/deploy-keys",
                [
                    'label' => $title,
                    'key' => $key,
                ]
            );

            if ($res->status() !== 200) {
                $errorBody = $res->json();
                $errorMessage = $errorBody['error']['message'] ?? $errorBody['error_description'] ?? $errorBody['error'] ?? $errorBody['message'] ?? $res->body();
                throw new FailedToDeployGitKey($errorMessage);
            }

            return $res->json()['id'] ?? '';
        } catch (Exception $e) {
            throw new FailedToDeployGitKey($e->getMessage());
        }
    }

    public function deleteDeployKey(string $keyId, string $repo): void
    {
        try {
            $response = Http::withHeaders($this->getAuthenticationHeaders())
                ->delete($this->apiUrl."/repositories/$repo/deploy-keys/$keyId");

            if (! $response->successful()) {
                Log::warning('Failed to delete Bitbucket deploy key', [
                    'repo' => $repo,
                    'key_id' => $keyId,
                    'response' => $response->body(),
                ]);
            }

        } catch (Exception $e) {
            Log::error('Error deleting Bitbucket deploy key', [
                'repo' => $repo,
                'key_id' => $keyId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Parse committer information from raw author string
     *
     * @param  string  $raw  Raw author string in format "Name <email@example.com>"
     * @return array<string, string> Array with 'name' and 'email' keys
     */
    protected function getCommitter(string $raw): array
    {
        $committer = explode(' <', $raw, 2);

        if (count($committer) < 2) {
            // Malformed input, return empty values
            // explode() always returns at least one element, so $committer[0] always exists
            return [
                'name' => $committer[0],
                'email' => '',
            ];
        }

        return [
            'name' => $committer[0],
            'email' => Str::replace('>', '', $committer[1]),
        ];
    }

    /**
     * Get authentication headers with access token
     * Token is cached for 10 minutes to avoid unnecessary API calls
     *
     * @return array<string, string>
     *
     * @throws Exception
     */
    private function getAuthenticationHeaders(): array
    {
        $accessToken = $this->getAccessToken();

        if ($accessToken === null) {
            throw new Exception('Unable to obtain Bitbucket access token. Make sure your OAuth consumer is marked as "private consumer" in Bitbucket settings.');
        }

        return [
            'Authorization' => 'Bearer '.$accessToken,
        ];
    }

    public function getWebhookBranch(array $payload): string
    {
        return data_get($payload, 'push.changes.0.new.name', 'default-branch');
    }

    public function getRepos(bool $useCache = true): array
    {
        $cacheKey = 'bitbucket_v2_repos_'.$this->sourceControl->id;

        if ($useCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $repos = $this->fetchAllPages('/repositories', [
                'pagelen' => self::MAX_PAGELEN,
                'role' => 'member', // Only repos where user is a member
            ]);

            $repoNames = $repos->pluck('full_name')->toArray();
            Cache::put($cacheKey, $repoNames, self::CACHE_TTL);

            return $repoNames;

        } catch (Throwable $e) {
            Log::error('Failed to fetch Bitbucket repositories', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    public function getBranches(string $repo, bool $useCache = true): array
    {
        $cacheKey = 'bitbucket_v2_branches_'.md5($repo.$this->sourceControl->id);

        if ($useCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $branches = $this->fetchAllPages("/repositories/$repo/refs/branches", [
                'pagelen' => self::MAX_PAGELEN,
            ]);

            $branchNames = $branches->pluck('name')->toArray();
            Cache::put($cacheKey, $branchNames, self::CACHE_TTL);

            return $branchNames;

        } catch (Throwable $e) {
            Log::error('Failed to fetch Bitbucket branches', [
                'repo' => $repo,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Fetch all pages from Bitbucket API
     * Bitbucket uses pagination with 'next' field and 'values' array
     *
     * @param  string  $endpoint  API endpoint (without base URL)
     * @param  array<string, mixed>  $params  Query parameters
     * @return Collection<int, mixed>
     */
    private function fetchAllPages(string $endpoint, array $params = []): Collection
    {
        $allData = collect();
        $nextUrl = $this->apiUrl.$endpoint.'?'.http_build_query($params);
        $pageCount = 0;

        while ($nextUrl !== null && $pageCount < self::MAX_PAGES) {
            try {
                $response = Http::withHeaders($this->getAuthenticationHeaders())
                    ->get($nextUrl);

                if (! $response->successful()) {
                    Log::error('Bitbucket API request failed', [
                        'endpoint' => $nextUrl,
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                    break;
                }

                $data = $response->json();

                if (isset($data['values']) && is_array($data['values'])) {
                    $allData = $allData->concat($data['values']);
                }

                // Bitbucket pagination uses 'next' field in the response
                $nextUrl = $data['next'] ?? null;
                $pageCount++;

            } catch (Throwable $e) {
                Log::error('Error fetching Bitbucket API page', [
                    'endpoint' => $nextUrl,
                    'error' => $e->getMessage(),
                ]);
                break;
            }
        }

        if ($pageCount >= self::MAX_PAGES) {
            Log::warning('Reached pagination limit', [
                'endpoint' => $endpoint,
                'pages_fetched' => $pageCount,
            ]);
        }

        return $allData;
    }
}
