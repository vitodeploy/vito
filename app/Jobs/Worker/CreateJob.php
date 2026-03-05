<?php

namespace App\Jobs\Worker;

use App\Enums\WorkerStatus;
use App\Models\ServerLog;
use App\Models\Service;
use App\Models\Worker;
use App\Services\ProcessManager\ProcessManager;
use App\Traits\UniqueQueue;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CreateJob implements ShouldQueue
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
            $processManager->create(
                $this->worker->id,
                $this->worker->command,
                $this->worker->user,
                $this->worker->auto_start,
                $this->worker->auto_restart,
                $this->worker->numprocs,
                $this->worker->getLogFile(),
                $this->worker->site?->path,
                $this->worker->site_id,
                $this->worker->environment,
            );
            $this->worker->status = WorkerStatus::RUNNING;
            $this->worker->save();
        });
    }

    public function failed(Exception $e): void
    {
        $this->worker->delete();
        ServerLog::log($this->worker->server, 'create-worker-failed', $e->getMessage());
    }
}
