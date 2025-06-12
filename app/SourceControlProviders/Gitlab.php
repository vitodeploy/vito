<?php

namespace App\SourceControlProviders;

use App\Exceptions\FailedToDeployGitHook;
use App\Exceptions\FailedToDeployGitKey;
use App\Exceptions\FailedToDestroyGitHook;
use Exception;
use Illuminate\Support\Facades\Http;

class Gitlab extends AbstractSourceControlProvider
{
    protected string $defaultApiHost = 'https://gitlab.com/';

    protected string $apiVersion = 'api/v4';

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
            ds($this->getApiUrl());
            $res = Http::withToken($this->data()['token'])
                ->get($this->getApiUrl().'/version');
        } catch (Exception) {
            return false;
        }

        ds($res->status());

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
    public function deployKey(string $title, string $repo, string $key): void
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
        } catch (Exception $e) {
            throw new FailedToDeployGitKey($e->getMessage());
        }

        if ($response->status() != 201) {
            throw new FailedToDeployGitKey($response->body());
        }
    }

    public function getApiUrl(): string
    {
        $host = $this->sourceControl->url ?? $this->defaultApiHost;

        return $host.$this->apiVersion;
    }

    public function getWebhookBranch(array $payload): string
    {
        return $payload['ref'] ?? '';
    }
}
