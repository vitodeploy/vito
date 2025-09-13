<?php

namespace App\SourceControlProviders;

use App\Exceptions\FailedToDeployGitHook;
use App\Exceptions\FailedToDeployGitKey;
use App\Exceptions\FailedToDestroyGitHook;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class Gitea extends AbstractSourceControlProvider
{
    protected string $defaultApiHost = 'https://gitea.com/';

    protected string $apiVersion = 'api/v1';

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
}
