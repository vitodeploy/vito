<?php

namespace App\Jobs\Worker;

use App\Actions\Component\ValidateComponentProcess;
use App\Actions\Site\BroadcastSiteUpdate;
use App\Actions\Worker\InstallWorker;
use App\Models\Worker;
use App\Traits\HandlesWorkerFailure;
use App\Traits\UniqueQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class CreateJob implements ShouldQueue
{
    use HandlesWorkerFailure;
    use Queueable;
    use UniqueQueue;

    public function __construct(protected Worker $worker) {}

    public function handle(): void
    {
        $this->run("server-{$this->worker->server_id}", function () {
            app(ValidateComponentProcess::class)->runQueued($this->worker->component, fn () => app(InstallWorker::class)->install($this->worker));
        });
    }

    public function failed(Throwable $e): void
    {
        $this->markWorkerFailed($this->worker, $e, 'create-worker-failed');

        if ($this->worker->site) {
            app(BroadcastSiteUpdate::class)->broadcast($this->worker->site);
        }
    }
}
