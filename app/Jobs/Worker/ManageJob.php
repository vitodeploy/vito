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

class ManageJob implements ShouldQueue
{
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
            $this->worker->save();
        });
    }

    public function failed(Exception $e): void
    {
        $this->worker->status = WorkerStatus::FAILED;
        $this->worker->save();
        ServerLog::log($this->worker->server, "{$this->action}-worker-failed", $e->getMessage());
    }
}
