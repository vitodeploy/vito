<?php

namespace App\Jobs\Worker;

use App\Actions\Site\BroadcastSiteUpdate;
use App\Enums\WorkerStatus;
use App\Models\Service;
use App\Models\Worker;
use App\Services\ProcessManager\ProcessManager;
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
            /** @var Service $service */
            $service = $this->worker->server->processManager();
            /** @var ProcessManager $processManager */
            $processManager = $service->handler();
            $processManager->create($this->worker);
            $this->worker->status = WorkerStatus::RUNNING;
            $this->worker->error = null;
            $this->worker->save();
            $this->broadcastWorkerUpdate($this->worker);

            if ($this->worker->site) {
                app(BroadcastSiteUpdate::class)->broadcast($this->worker->site);
            }
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
