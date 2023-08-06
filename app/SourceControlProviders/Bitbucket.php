<?php

namespace App\SourceControlProviders;

use App\Exceptions\FailedToDeployGitHook;
use App\Exceptions\FailedToDeployGitKey;
use App\Exceptions\FailedToDestroyGitHook;
use App\Exceptions\RepositoryNotFound;
use App\Exceptions\RepositoryPermissionDenied;
use App\Exceptions\SourceControlIsNotConnected;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class Bitbucket extends AbstractSourceControlProvider
{
    protected string $apiUrl = 'https://api.bitbucket.org/2.0';

    public function connect(): bool
    {
        $res = Http::withToken($this->sourceControl->access_token)
            ->get($this->apiUrl.'/repositories');

        return $res->successful();
    }

    /**
     * @throws Exception
     */
    public function getRepo(string $repo = null): mixed
    {
        $res = Http::withToken($this->sourceControl->access_token)
            ->get($this->apiUrl."/repositories/$repo");

        $this->handleResponseErrors($res, $repo);

        return $res->json();
    }

    public function fullRepoUrl(string $repo, string $key): string
    {
        return sprintf("git@bitbucket.org-%s:%s.git", $key, $repo);
    }

    /**
     * @throws FailedToDeployGitHook
     */
    public function deployHook(string $repo, array $events, string $secret): array
    {
        $response = Http::withToken($this->sourceControl->access_token)->post($this->apiUrl."/repositories/$repo/hooks", [
            'description' => 'deploy',
            'url' => url('/git-hooks?secret='.$secret),
            'events' => [
                'repo:'.implode(',', $events),
            ],
            'active' => true,
        ]);

        if ($response->status() != 201) {
            throw new FailedToDeployGitHook($response->json()['error']['message']);
        }

        return [
            'hook_id' => json_decode($response->body())->uuid,
            'hook_response' => json_decode($response->body()),
        ];
    }

    /**
     * @throws FailedToDestroyGitHook
     */
    public function destroyHook(string $repo, string $hookId): void
    {
        $hookId = urlencode($hookId);
        $response = Http::withToken($this->sourceControl->access_token)->delete($this->apiUrl."/repositories/$repo/hooks/$hookId");

        if ($response->status() != 204) {
            throw new FailedToDestroyGitHook('Error');
        }
    }

    /**
     * @throws Exception
     */
    public function getLastCommit(string $repo, string $branch): ?array
    {
        $res = Http::withToken($this->sourceControl->access_token)
            ->get($this->apiUrl."/repositories/$repo/commits?include=".$branch);

        $this->handleResponseErrors($res, $repo);

        $commits = $res->json();

        if (isset($commits['values']) && count($commits['values']) > 0) {
            return [
                'commit_id' => $commits['values'][0]['hash'],
                'commit_data' => [
                    'name' => $this->getCommitter($commits['values'][0]['author']['raw'])['name'] ?? null,
                    'email' => $this->getCommitter($commits['values'][0]['author']['raw'])['email'] ?? null,
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
    public function deployKey(string $title, string $repo, string $key): void
    {
        $res = Http::withToken($this->sourceControl->access_token)->post(
            $this->apiUrl."/repositories/$repo/deploy-keys",
            [
                'label' => $title,
                'key' => $key,
            ]
        );

        if ($res->status() != 201) {
            throw new FailedToDeployGitKey($res->json()['error']['message']);
        }
    }

    protected function getCommitter(string $raw): array
    {
        $committer = explode(' <', $raw);

        return [
            'name' => $committer[0],
            'email' => Str::replace('>', '', $committer[1]),
        ];
    }
}
