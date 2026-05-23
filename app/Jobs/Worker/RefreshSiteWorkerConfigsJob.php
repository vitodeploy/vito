<?php

namespace App\Jobs\Worker;

use App\Actions\Worker\RefreshSiteWorkerConfigs;
use App\Models\ServerLog;
use App\Models\Site;
use App\Traits\UniqueQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Background job that rewrites supervisor configs for workers on the site's
 * sibling-set after a Tooling install/uninstall, and selectively restarts
 * workers whose `command` references the changed tool. Decoupled from
 * `InstallSiteToolingJob` / `UninstallSiteToolingJob` so the user's install
 * UI doesn't block on supervisor graceful-shutdown windows.
 */
class RefreshSiteWorkerConfigsJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;
    use UniqueQueue;

    public function __construct(
        protected Site $site,
        protected string $toolId,
    ) {
        $this->onQueue('ssh');
    }

    public function uniqueId(): string
    {
        return "worker-refresh:{$this->site->id}:{$this->toolId}";
    }

    public function handle(): void
    {
        $this->run("server-{$this->site->server_id}", function (): void {
            app(RefreshSiteWorkerConfigs::class)->refresh($this->site, $this->toolId);
        });
    }

    public function failed(Throwable $e): void
    {
        ServerLog::log(
            $this->site->server,
            "site-tooling-worker-refresh-job-failed-{$this->toolId}",
            $e->getMessage(),
            $this->site,
        );
    }
}
