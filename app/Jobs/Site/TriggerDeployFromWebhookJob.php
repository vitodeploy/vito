<?php

namespace App\Jobs\Site;

use App\Actions\Site\Deploy;
use App\Facades\Notifier;
use App\Models\ServerLog;
use App\Models\Site;
use App\Notifications\WebhookDeploymentFailed;
use App\Traits\UniqueQueue;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class TriggerDeployFromWebhookJob implements ShouldQueue
{
    use Queueable;
    use UniqueQueue;

    public function __construct(
        protected int $siteId,
    ) {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        $this->run("trigger-deploy-{$this->siteId}", function () {
            $site = Site::find($this->siteId);
            if (! $site instanceof Site) {
                return;
            }

            try {
                app(Deploy::class)->run($site);
            } catch (Throwable $e) {
                ServerLog::log($site->server, 'deploy-failed', $e->getMessage(), $site);
                Log::error('webhook-deploy-failed', [
                    'site_id' => $site->id,
                    'error' => $e->getMessage(),
                ]);
                Notifier::send($site, new WebhookDeploymentFailed($site));
            }
        });
    }

    public function failed(Exception $e): void
    {
        Log::error('webhook-deploy-job-failed', [
            'site_id' => $this->siteId,
            'error' => $e->getMessage(),
        ]);
    }
}
