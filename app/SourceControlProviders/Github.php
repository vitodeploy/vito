<?php

namespace App\SourceControlProviders;

use App\Exceptions\FailedToDeployGitHook;
use App\Exceptions\FailedToDeployGitKey;
use App\Exceptions\FailedToDestroyGitHook;
use Exception;
use Illuminate\Support\Facades\Http;

class Github extends AbstractSourceControlProvider
{
    protected string $apiUrl = 'https://api.github.com';

    public function connect(): bool
    {
        $res = Http::withHeaders([
            'Accept' => 'application/vnd.github.v3+json',
            'Authorization' => 'Bearer '.$this->data()['token'],
        ])->get($this->apiUrl.'/user/repos');

        return $res->successful();
    }

    /**
     * @throws Exception
     */
    public function getRepo(?string $repo = null): mixed
    {
        if ($repo) {
            $url = $this->apiUrl.'/repos/'.$repo;
        } else {
            $url = $this->apiUrl.'/user/repos';
        }
        $res = Http::withHeaders([
            'Accept' => 'application/vnd.github.v3+json',
            'Authorization' => 'Bearer '.$this->data()['token'],
        ])->get($url);

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
        $response = Http::withHeaders([
            'Accept' => 'application/vnd.github.v3+json',
            'Authorization' => 'Bearer '.$this->data()['token'],
        ])->post($this->apiUrl."/repos/$repo/hooks", [
            'name' => 'web',
            'events' => $events,
            'config' => [
                'url' => url('/api/git-hooks?secret='.$secret),
                'content_type' => 'json',
            ],
            'active' => true,
        ]);

        if ($response->status() != 201) {
            throw new FailedToDeployGitHook(json_decode($response->body())->message);
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
        $response = Http::withHeaders([
            'Accept' => 'application/vnd.github.v3+json',
            'Authorization' => 'Bearer '.$this->data()['token'],
        ])->delete($this->apiUrl."/repos/$repo/hooks/$hookId");

        if ($response->status() != 204) {
            throw new FailedToDestroyGitHook(json_decode($response->body())->message);
        }
    }

    /**
     * @throws Exception
     */
    public function getLastCommit(string $repo, string $branch): ?array
    {
        $url = $this->apiUrl.'/repos/'.$repo.'/commits/'.$branch;
        $res = Http::withHeaders([
            'Accept' => 'application/vnd.github.v3+json',
            'Authorization' => 'Bearer '.$this->data()['token'],
        ])->get($url);

        $this->handleResponseErrors($res, $repo);

        $commit = $res->json();
        if (isset($commit['sha']) && isset($commit['commit'])) {
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

    /**
     * @throws FailedToDeployGitKey
     */
    public function deployKey(string $title, string $repo, string $key): void
    {
        $response = Http::withToken($this->data()['token'])->post(
            $this->apiUrl.'/repos/'.$repo.'/keys',
            [
                'title' => $title,
                'key' => $key,
                'read_only' => false,
            ]
        );

        if ($response->status() != 201) {
            throw new FailedToDeployGitKey(json_decode($response->body())->message);
        }
    }
}
