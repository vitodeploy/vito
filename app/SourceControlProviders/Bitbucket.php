<?php

namespace App\SourceControlProviders;

use App\Exceptions\FailedToDeployGitHook;
use App\Exceptions\FailedToDeployGitKey;
use App\Exceptions\FailedToDestroyGitHook;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class Bitbucket extends AbstractSourceControlProvider
{
    protected string $apiUrl = 'https://api.bitbucket.org/2.0';

    public function createRules(array $input): array
    {
        return [
            'username' => 'required',
            'password' => 'required',
        ];
    }

    public function createData(array $input): array
    {
        return [
            'username' => $input['username'] ?? '',
            'password' => $input['password'] ?? '',
        ];
    }

    public function data(): array
    {
        return [
            'username' => $this->sourceControl->provider_data['username'] ?? '',
            'password' => $this->sourceControl->provider_data['password'] ?? '',
        ];
    }

    public function connect(): bool
    {
        $res = Http::withHeaders($this->getAuthenticationHeaders())
            ->get($this->apiUrl.'/repositories');

        return $res->successful();
    }

    /**
     * @throws Exception
     */
    public function getRepo(?string $repo = null): mixed
    {
        $res = Http::withHeaders($this->getAuthenticationHeaders())
            ->get($this->apiUrl."/repositories/$repo");

        $this->handleResponseErrors($res, $repo);

        return $res->json();
    }

    public function fullRepoUrl(string $repo, string $key): string
    {
        return sprintf('git@bitbucket.org-%s:%s.git', $key, $repo);
    }

    /**
     * @throws FailedToDeployGitHook
     */
    public function deployHook(string $repo, array $events, string $secret): array
    {
        $response = Http::withHeaders($this->getAuthenticationHeaders())
            ->post($this->apiUrl."/repositories/$repo/hooks", [
                'description' => 'deploy',
                'url' => url('/api/git-hooks?secret='.$secret),
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
        $response = Http::withHeaders($this->getAuthenticationHeaders())
            ->delete($this->apiUrl."/repositories/$repo/hooks/$hookId");

        if ($response->status() != 204) {
            throw new FailedToDestroyGitHook('Error');
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
        $res = Http::withHeaders($this->getAuthenticationHeaders())->post(
            $this->apiUrl."/repositories/$repo/deploy-keys",
            [
                'label' => $title,
                'key' => $key,
            ]
        );

        if ($res->status() != 200) {
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

    private function getAuthenticationHeaders(): array
    {
        $username = $this->data()['username'];
        $password = $this->data()['password'];
        $basicAuth = base64_encode("$username:$password");

        return [
            'Authorization' => 'Basic '.$basicAuth,
        ];
    }
}
