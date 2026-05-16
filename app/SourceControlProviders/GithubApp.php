<?php

namespace App\SourceControlProviders;

use App\Exceptions\FailedToDeployGitHook;
use App\Exceptions\FailedToDestroyGitHook;
use App\Models\GithubApp as GithubAppModel;
use BadMethodCallException;
use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class GithubApp extends AbstractSourceControlProvider
{
    private const string API_BASE_URL = 'https://api.github.com';

    private const int TOKEN_TTL = 50 * 60; // 50 minutes (GitHub installation tokens last ~60min)

    private const int CACHE_TTL = 60 * 15;

    private const int MAX_PER_PAGE = 100;

    private const int MAX_PAGES = 25;

    public static function id(): string
    {
        return 'github-app';
    }

    public function createRules(array $input): array
    {
        return [];
    }

    public function createData(array $input): array
    {
        return [];
    }

    public function data(): array
    {
        return [
            'installation_id' => $this->sourceControl->external_identifier,
            'account_login' => $this->sourceControl->provider_data['account_login'] ?? null,
            'account_type' => $this->sourceControl->provider_data['account_type'] ?? null,
            'html_url' => $this->sourceControl->provider_data['html_url'] ?? null,
        ];
    }

    public function connect(): bool
    {
        try {
            $res = $this->getClient()->get(self::API_BASE_URL.'/installation/repositories', [
                'per_page' => 1,
            ]);

            return $res->successful();
        } catch (Exception $e) {
            Log::error('GitHub App connection failed', [
                'installation_id' => $this->sourceControl->external_identifier,
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
        $res = $this->getClient()->get(self::API_BASE_URL.'/repos/'.$repo);

        $this->handleResponseErrors($res, $repo);

        return $res->json();
    }

    public function fullRepoUrl(string $repo, string $key): string
    {
        throw new BadMethodCallException('fullRepoUrl is not yet implemented for the GitHub App provider.');
    }

    /**
     * @throws FailedToDeployGitHook
     */
    public function deployHook(string $repo, array $events, string $secret): array
    {
        throw new BadMethodCallException('deployHook is not yet implemented for the GitHub App provider.');
    }

    /**
     * @throws FailedToDestroyGitHook
     */
    public function destroyHook(string $repo, string $hookId): void
    {
        throw new BadMethodCallException('destroyHook is not yet implemented for the GitHub App provider.');
    }

    /**
     * @throws Exception
     */
    public function getLastCommit(string $repo, string $branch): ?array
    {
        $url = self::API_BASE_URL.'/repos/'.$repo.'/commits/'.$branch;
        $res = $this->getClient()->get($url);
        $this->handleResponseErrors($res, $repo);

        $commit = $res->json();

        if (isset($commit['sha'], $commit['commit'])) {
            return [
                'commit_id' => $commit['sha'],
                'commit_data' => [
                    'name' => $commit['commit']['committer']['name'] ?? null,
                    'email' => $commit['commit']['committer']['email'] ?? null,
                    'message' => $commit['commit']['message'] ?? null,
                    'url' => $commit['html_url'] ?? null,
                ],
            ];
        }

        return null;
    }

    public function deployKey(string $title, string $repo, string $key): string
    {
        throw new BadMethodCallException('deployKey is not yet implemented for the GitHub App provider.');
    }

    public function deleteDeployKey(string $keyId, string $repo): void
    {
        throw new BadMethodCallException('deleteDeployKey is not yet implemented for the GitHub App provider.');
    }

    public function getRepos(bool $useCache = true): array
    {
        $installationId = $this->sourceControl->external_identifier;
        $cacheKey = "github_app_repos_{$installationId}";

        if ($useCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $repos = $this->fetchAllPages('/installation/repositories', [
                'per_page' => self::MAX_PER_PAGE,
            ], wrapKey: 'repositories');

            $names = $repos->pluck('full_name')->toArray();
            Cache::put($cacheKey, $names, self::CACHE_TTL);

            return $names;
        } catch (Throwable $e) {
            Log::error('Failed to fetch GitHub App repositories', [
                'installation_id' => $installationId,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    public function getBranches(string $repo, bool $useCache = true): array
    {
        $installationId = $this->sourceControl->external_identifier;
        $cacheKey = "github_app_branches_{$installationId}_".md5($repo);

        if ($useCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $branches = $this->fetchAllPages("/repos/{$repo}/branches", [
                'per_page' => self::MAX_PER_PAGE,
            ]);

            $names = $branches->pluck('name')->toArray();
            Cache::put($cacheKey, $names, self::CACHE_TTL);

            return $names;
        } catch (Throwable $e) {
            Log::error('Failed to fetch GitHub App branches', [
                'installation_id' => $installationId,
                'repo' => $repo,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    public function installationAccessToken(): string
    {
        $installationId = $this->sourceControl->external_identifier;
        if (! $installationId) {
            throw new RuntimeException('SourceControl is missing external_identifier (installation id).');
        }

        return Cache::remember(
            "github_app_token_{$installationId}",
            self::TOKEN_TTL,
            function () use ($installationId): string {
                $app = GithubAppModel::current();
                if (! $app) {
                    throw new RuntimeException('GitHub App is not configured.');
                }

                $res = self::appClient($app)
                    ->post(self::API_BASE_URL."/app/installations/{$installationId}/access_tokens");

                if (! $res->successful()) {
                    throw new RuntimeException('Failed to mint installation access token (HTTP '.$res->status().')');
                }

                $token = $res->json('token');
                if (! is_string($token) || $token === '') {
                    throw new RuntimeException('GitHub did not return an installation token.');
                }

                return $token;
            }
        );
    }

    public static function appClient(GithubAppModel $app): PendingRequest
    {
        return Http::withHeaders([
            'Accept' => 'application/vnd.github+json',
            'Authorization' => 'Bearer '.self::buildAppJwt($app),
            'X-GitHub-Api-Version' => '2022-11-28',
        ]);
    }

    public static function buildAppJwt(GithubAppModel $app): string
    {
        $now = time();
        $header = self::base64UrlEncode((string) json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $payload = self::base64UrlEncode((string) json_encode([
            'iat' => $now - 60,
            'exp' => $now + (9 * 60),
            'iss' => $app->app_id,
        ]));

        $signingInput = $header.'.'.$payload;
        $signature = '';
        $key = openssl_pkey_get_private($app->private_key);
        if ($key === false) {
            throw new RuntimeException('GitHub App private key could not be parsed.');
        }

        if (! openssl_sign($signingInput, $signature, $key, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Failed to sign GitHub App JWT.');
        }

        return $signingInput.'.'.self::base64UrlEncode($signature);
    }

    private static function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function getClient(): PendingRequest
    {
        return Http::withHeaders([
            'Accept' => 'application/vnd.github+json',
            'Authorization' => 'Bearer '.$this->installationAccessToken(),
            'X-GitHub-Api-Version' => '2022-11-28',
        ]);
    }

    /**
     * @param  array<string, mixed>  $params
     *
     * @throws RequestException
     */
    private function fetchAllPages(string $endpoint, array $params = [], ?string $wrapKey = null): Collection
    {
        $allData = collect();
        $page = 1;
        $hasMore = true;

        while ($hasMore) {
            $params['page'] = $page;
            $response = $this->getClient()->get(self::API_BASE_URL.$endpoint, $params);

            if (! $response->successful()) {
                throw new RequestException($response);
            }

            $body = $response->json();
            $pageData = $wrapKey ? ($body[$wrapKey] ?? []) : $body;

            if (empty($pageData)) {
                $hasMore = false;
            } else {
                $allData = $allData->concat($pageData);

                if (count($pageData) < $params['per_page']) {
                    $hasMore = false;
                }

                $page++;
            }

            if ($page > self::MAX_PAGES) {
                Log::warning('Reached pagination limit on GitHub App endpoint', [
                    'endpoint' => $endpoint,
                ]);
                break;
            }
        }

        return $allData;
    }
}
