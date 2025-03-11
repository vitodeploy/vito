<?php

namespace App\Actions\Queue;

use App\Enums\QueueStatus;
use App\Models\Queue;

class ManageQueue
{
    public function start(Queue $queue): void
    {
        $queue->status = QueueStatus::STARTING;
        $queue->save();
        dispatch(function () use ($queue): void {
            $service = $queue->server->processManager();
            if (! $service) {
                throw new \Exception('Process manager service not found');
            }
            /** @var \App\SSH\Services\ProcessManager\ProcessManager $handler */
            $handler = $service->handler();
            $handler->start($queue->id, $queue->site_id);
            $queue->status = QueueStatus::RUNNING;
            $queue->save();
        })->onConnection('ssh');
    }

    public function stop(Queue $queue): void
    {
        $queue->status = QueueStatus::STOPPING;
        $queue->save();
        dispatch(function () use ($queue): void {
            $service = $queue->server->processManager();
            if (! $service) {
                throw new \Exception('Process manager service not found');
            }
            /** @var \App\SSH\Services\ProcessManager\ProcessManager $handler */
            $handler = $service->handler();
            $handler->stop($queue->id, $queue->site_id);
            $queue->status = QueueStatus::STOPPED;
            $queue->save();
        })->onConnection('ssh');
    }

    public function restart(Queue $queue): void
    {
        $queue->status = QueueStatus::RESTARTING;
        $queue->save();
        dispatch(function () use ($queue): void {
            $service = $queue->server->processManager();
            if (! $service) {
                throw new \Exception('Process manager service not found');
            }
            /** @var \App\SSH\Services\ProcessManager\ProcessManager $handler */
            $handler = $service->handler();
            $handler->restart($queue->id, $queue->site_id);
            $queue->status = QueueStatus::RUNNING;
            $queue->save();
        })->onConnection('ssh');
    }
}
