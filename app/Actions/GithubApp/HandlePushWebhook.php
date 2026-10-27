<?php

namespace App\Actions\GithubApp;

use App\Actions\GitHook\TriggerRepositoryAutoDeploy;
use App\Jobs\Site\TriggerDeployFromWebhookJob;
use App\Models\Site;
use App\Models\SourceControl;

class HandlePushWebhook
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(array $payload): void
    {
        $installationId = (int) ($payload['installation']['id'] ?? 0);
        $repository = (string) ($payload['repository']['full_name'] ?? '');
        $branch = str((string) ($payload['ref'] ?? ''))->after('refs/heads/')->toString();

        if ($installationId === 0 || $repository === '' || $branch === '') {
            return;
        }

        $sourceControl = SourceControl::query()
            ->where('provider', SourceControl::PROVIDER_GITHUB_APP)
            ->where('external_identifier', (string) $installationId)
            ->first();

        if (! $sourceControl instanceof SourceControl) {
            return;
        }

        if (str_starts_with((string) ($payload['ref'] ?? ''), 'refs/heads/') && ! ($payload['deleted'] ?? false)) {
            app(TriggerRepositoryAutoDeploy::class)->trigger($sourceControl, $repository, $branch);
        }

        $sites = Site::query()
            ->with('gitHook')
            ->where('source_control_id', $sourceControl->id)
            ->where('repository', $repository)
            ->where('branch', $branch)
            ->get();

        foreach ($sites as $site) {
            $gitHook = $site->gitHook;
            if (! $gitHook) {
                continue;
            }
            if (! in_array('deploy', (array) $gitHook->actions, true)) {
                continue;
            }

            TriggerDeployFromWebhookJob::dispatch($site->id);
        }
    }
}
