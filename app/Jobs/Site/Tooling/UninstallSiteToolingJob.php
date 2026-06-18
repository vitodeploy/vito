<?php

namespace App\Jobs\Site\Tooling;

use App\Jobs\Worker\RefreshSiteWorkerConfigsJob;
use App\Models\ServerLog;
use App\Models\Site;
use App\Tooling\SiteToolingState;
use App\Tooling\ToolingRegistry;
use App\Traits\UniqueQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use RuntimeException;
use Throwable;

class UninstallSiteToolingJob implements ShouldBeUnique, ShouldQueue
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
        return "site-tooling:{$this->site->id}:{$this->toolId}";
    }

    public function handle(): void
    {
        $completed = false;

        $this->run("server-{$this->site->server_id}", function () use (&$completed): void {
            $tool = ToolingRegistry::find($this->toolId);

            if (! $tool) {
                throw new RuntimeException("Tooling '{$this->toolId}' is not registered; it may have been removed or the worker is out of sync.");
            }

            $tool->uninstall($this->site);

            SiteToolingState::completeUninstall($this->site, $this->toolId);

            $completed = true;
        });

        if ($completed) {
            dispatch(new RefreshSiteWorkerConfigsJob($this->site, $this->toolId));
        }
    }

    public function failed(Throwable $e): void
    {
        ServerLog::log(
            $this->site->server,
            "site-tooling-uninstall-{$this->toolId}-failed",
            $e->getMessage(),
            $this->site,
        );

        SiteToolingState::setStatus($this->site, $this->toolId, SiteToolingState::STATUS_UNINSTALL_FAILED);
    }
}
