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
