<?php

namespace App\Actions\Worker;

use App\Actions\Component\HasComponentRelease;
use App\Actions\Component\ValidateComponentProcess;
use App\Enums\WorkerStatus;
use App\Jobs\Worker\ManageJob;
use App\Models\Worker;
use Illuminate\Validation\ValidationException;

class ManageWorker
{
    public function start(Worker $worker): void
    {
        $this->validate($worker, true);
        $worker->status = WorkerStatus::STARTING;
        $worker->error = null;
        $worker->save();
        dispatch(new ManageJob($worker, 'start', WorkerStatus::RUNNING))->onQueue('ssh');
    }

    public function stop(Worker $worker): void
    {
        $this->validate($worker, false);
        $worker->status = WorkerStatus::STOPPING;
        $worker->error = null;
        $worker->save();
        dispatch(new ManageJob($worker, 'stop', WorkerStatus::STOPPED))->onQueue('ssh');
    }

    public function restart(Worker $worker): void
    {
        $this->validate($worker, true);
        $worker->status = WorkerStatus::RESTARTING;
        $worker->error = null;
        $worker->save();
        dispatch(new ManageJob($worker, 'restart', WorkerStatus::RUNNING))->onQueue('ssh');
    }

    public function validate(Worker $worker, bool $starting): void
    {
        app(ValidateComponentProcess::class)->validate($worker->server, [], $worker->component, 'worker', $worker);
        if ($starting && $worker->component && ! app(HasComponentRelease::class)->check($worker->component)) {
            throw ValidationException::withMessages(['name' => 'Deploy this component before starting its processes.']);
        }
    }
}
