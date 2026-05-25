<?php

namespace App\Actions\Worker;

use App\Enums\WorkerStatus;
use App\Traits\HandlesWorkerFailure;
use App\Models\Site;
use App\Services\ProcessManager\ProcessManager;
use Throwable;

class RestartSiteWorkers
{
    use HandlesWorkerFailure;

    public function restart(Site $site): void
    {
        /** @var ProcessManager $handler */
        $handler = $site->server->processManager()->handler();

        foreach ($site->loadMissing('workers')->workers as $worker) {
            try {
                $handler->restart($worker->id, $site->id);
                $worker->status = WorkerStatus::RUNNING;
                $worker->error = null;
                $worker->save();
                $this->broadcastWorkerUpdate($worker);
            } catch (Throwable $e) {
                $this->markWorkerFailed($worker, $e, 'deploy-restart-worker-failed');
            }
        }
    }
}
