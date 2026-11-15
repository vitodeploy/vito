<?php

namespace App\Actions\Worker;

use App\Actions\Component\HasComponentRelease;
use App\Actions\Component\ValidateComponentProcess;
use App\Actions\Site\BroadcastSiteUpdate;
use App\Enums\WorkerStatus;
use App\Models\Worker;
use App\Services\ProcessManager\ProcessManager;
use App\Traits\HandlesWorkerFailure;

class InstallWorker
{
    use HandlesWorkerFailure;

    public function install(Worker $worker, bool $replace = false): void
    {
        $worker->refresh();
        if ($worker->component && $worker->component_capability_id === null) {
            app(ValidateComponentProcess::class)->validate($worker->server, [], $worker->component, 'worker', $worker);
        }
        /** @var ProcessManager $handler */
        $handler = $worker->server->processManager()->handler();
        $configuration = clone $worker;
        if ($worker->component) {
            $configuration->auto_start = $worker->auto_start && app(HasComponentRelease::class)->check($worker->component);
        }
        if ($replace) {
            $handler->delete($worker->id, $worker->site_id);
        }
        $handler->create($configuration);
        $worker->status = $worker->component_id !== null && ! $configuration->auto_start ? WorkerStatus::STOPPED : WorkerStatus::RUNNING;
        $worker->error = null;
        $worker->save();
        $this->broadcastWorkerUpdate($worker);

        if ($worker->site) {
            app(BroadcastSiteUpdate::class)->broadcast($worker->site);
        }
    }
}
