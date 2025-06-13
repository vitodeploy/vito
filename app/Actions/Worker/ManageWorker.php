<?php

namespace App\Actions\Worker;

use App\Enums\WorkerStatus;
use App\Models\Service;
use App\Models\Worker;
use App\Services\ProcessManager\ProcessManager;

class ManageWorker
{
    public function start(Worker $worker): void
    {
        $worker->status = WorkerStatus::STARTING;
        $worker->save();
        dispatch(function () use ($worker): void {
            /** @var Service $service */
            $service = $worker->server->processManager();
            /** @var ProcessManager $handler */
            $handler = $service->handler();
            $handler->start($worker->id, $worker->site_id);
            $worker->status = WorkerStatus::RUNNING;
            $worker->save();
        })->onConnection('ssh');
    }

    public function stop(Worker $worker): void
    {
        $worker->status = WorkerStatus::STOPPING;
        $worker->save();
        dispatch(function () use ($worker): void {
            /** @var Service $service */
            $service = $worker->server->processManager();
            /** @var ProcessManager $handler */
            $handler = $service->handler();
            $handler->stop($worker->id, $worker->site_id);
            $worker->status = WorkerStatus::STOPPED;
            $worker->save();
        })->onConnection('ssh');
    }

    public function restart(Worker $worker): void
    {
        $worker->status = WorkerStatus::RESTARTING;
        $worker->save();
        dispatch(function () use ($worker): void {
            /** @var Service $service */
            $service = $worker->server->processManager();
            /** @var ProcessManager $handler */
            $handler = $service->handler();
            $handler->restart($worker->id, $worker->site_id);
            $worker->status = WorkerStatus::RUNNING;
            $worker->save();
        })->onConnection('ssh');
    }
}
