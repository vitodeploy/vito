<?php

namespace App\Jobs\Worker;

use App\DTOs\SocketEventDTO;
use App\Enums\WorkerStatus;
use App\Events\SocketEvent;
use App\Http\Resources\WorkerResource;
use App\Models\ServerLog;
use App\Models\Service;
use App\Models\Worker;
use App\Services\ProcessManager\ProcessManager;
use App\Traits\UniqueQueue;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class EditJob implements ShouldQueue
{
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
            $processManager->delete($this->worker->id, $this->worker->site_id);

            $processManager->create($this->worker);
            $this->worker->status = WorkerStatus::RUNNING;
            $this->worker->save();
            $this->broadcastWorkerUpdate();
        });
    }

    public function failed(Exception $e): void
    {
        $this->worker->status = WorkerStatus::FAILED;
        $this->worker->save();
        $this->broadcastWorkerUpdate();
        ServerLog::log($this->worker->server, 'edit-worker-failed', $e->getMessage());
    }

    private function broadcastWorkerUpdate(): void
    {
        $this->worker->refresh();

        SocketEvent::dispatch(new SocketEventDTO(
            projectId: $this->worker->server->project_id,
            type: 'worker.updated',
            data: new WorkerResource($this->worker),
        ));
    }
}
