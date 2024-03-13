<?php

namespace App\Actions\Queue;

use App\Enums\QueueStatus;
use App\Enums\ServiceStatus;
use App\Models\Queue;

class ManageQueue
{
    public function start(Queue $queue): void
    {
        $queue->status = QueueStatus::STARTING;
        $queue->save();
        dispatch(function () use ($queue) {
            $queue->server->processManager()->handler()->start($queue->id, $queue->site_id);
            $queue->status = ServiceStatus::READY;
            $queue->save();
        })->catch(function () use ($queue) {
            $queue->status = ServiceStatus::FAILED;
            $queue->save();
        })->onConnection('ssh');
    }

    public function stop(Queue $queue): void
    {
        $queue->status = QueueStatus::STOPPING;
        $queue->save();
        dispatch(function () use ($queue) {
            $queue->server->processManager()->handler()->stop($queue->id, $queue->site_id);
            $queue->status = ServiceStatus::STOPPED;
            $queue->save();
        })->catch(function () use ($queue) {
            $queue->status = ServiceStatus::FAILED;
            $queue->save();
        })->onConnection('ssh');
    }

    public function restart(Queue $queue): void
    {
        $queue->status = QueueStatus::RESTARTING;
        $queue->save();
        dispatch(function () use ($queue) {
            $queue->server->processManager()->handler()->restart($queue->id, $queue->site_id);
            $queue->status = ServiceStatus::READY;
            $queue->save();
        })->catch(function () use ($queue) {
            $queue->status = ServiceStatus::FAILED;
            $queue->save();
        })->onConnection('ssh');
    }
}
