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

class ManageJob implements ShouldQueue
{
    use HandlesWorkerFailure;
    use Queueable;
    use UniqueQueue;

    public function __construct(
        protected Worker $worker,
        protected string $action,
        protected WorkerStatus $successStatus,
    ) {}

    public function handle(): void
    {
        $this->run("server-{$this->worker->server_id}", function () {
            /** @var Service $service */
            $service = $this->worker->server->processManager();
            /** @var ProcessManager $handler */
            $handler = $service->handler();
            $handler->{$this->action}($this->worker->id, $this->worker->site_id);
            $this->worker->status = $this->successStatus;
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
        $this->markWorkerFailed($this->worker, $e, "{$this->action}-worker-failed");

        if ($this->worker->site) {
            app(BroadcastSiteUpdate::class)->broadcast($this->worker->site);
        }
    }
}
