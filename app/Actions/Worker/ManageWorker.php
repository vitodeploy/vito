<?php

namespace App\Actions\Worker;

use App\Enums\WorkerStatus;
use App\Jobs\Worker\ManageJob;
use App\Models\Worker;

class ManageWorker
{
    public function start(Worker $worker): void
    {
        $worker->status = WorkerStatus::STARTING;
        $worker->error = null;
        $worker->save();
        dispatch(new ManageJob($worker, 'start', WorkerStatus::RUNNING))->onQueue('ssh');
    }

    public function stop(Worker $worker): void
    {
        $worker->status = WorkerStatus::STOPPING;
        $worker->error = null;
        $worker->save();
        dispatch(new ManageJob($worker, 'stop', WorkerStatus::STOPPED))->onQueue('ssh');
    }

    public function restart(Worker $worker): void
    {
        $worker->status = WorkerStatus::RESTARTING;
        $worker->error = null;
        $worker->save();
        dispatch(new ManageJob($worker, 'restart', WorkerStatus::RUNNING))->onQueue('ssh');
    }
}
