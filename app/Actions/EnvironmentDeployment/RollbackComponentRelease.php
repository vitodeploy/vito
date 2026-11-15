<?php

namespace App\Actions\EnvironmentDeployment;

use App\Actions\Component\RestartComponentWorkers;
use App\Enums\WorkerStatus;
use App\Models\Deployment;
use App\Services\ProcessManager\ProcessManager;
use RuntimeException;

class RollbackComponentRelease
{
    public function rollback(Deployment $deployment): void
    {
        if (! $deployment->release_started) {
            return;
        }
        $component = $deployment->component;
        $previous = $deployment->previousDeployment;
        $component->server->ssh($component->user)->setLog($deployment->log)->exec(view('ssh.applications.rollback-release', [
            'component' => $component, 'deployment' => $deployment, 'previousPath' => $previous?->path(),
            'restoreEnv' => ! $deployment->environmentDeployment->rollback_of_id,
        ]));
        if ($previous) {
            $previous->activate();
            app(RestartComponentWorkers::class)->restart($component);
        } else {
            $deployment->active = false;
            $deployment->save();
            foreach ($component->workers()->get() as $worker) {
                /** @var ProcessManager $handler */
                $handler = ($component->server->processManager() ?? throw new RuntimeException('Process manager is unavailable.'))->handler();
                $handler->stop($worker->id);
                $worker->status = WorkerStatus::STOPPED;
                $worker->save();
            }
        }
    }
}
