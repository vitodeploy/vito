<?php

namespace App\Actions\EnvironmentDeployment;

use App\Enums\EnvironmentDeploymentStatus;
use App\Jobs\EnvironmentDeployment\StartEnvironmentDeploymentJob;
use App\Models\Application;
use App\Models\Environment;
use App\Models\EnvironmentDeployment;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class FinishEnvironmentDeployment
{
    public function finish(int $runId): void
    {
        $run = EnvironmentDeployment::query()->find($runId);
        if (! $run || ! $run->status->isTerminal()) {
            return;
        }
        try {
            foreach ($run->deployments()->with('component.environment', 'component.server')->get() as $deployment) {
                if (Cache::lock('unique-queue:component-'.$deployment->component_id, 1260)->get(fn () => $deployment->pruneOldReleases()) === false) {
                    throw new LockTimeoutException;
                }
            }
        } finally {
            $this->next($run->environment);
        }
    }

    public function next(Environment $environment): void
    {
        DB::transaction(function () use ($environment): void {
            Application::query()->lockForUpdate()->findOrFail($environment->application_id);
            $environment = Environment::query()->lockForUpdate()->findOrFail($environment->id);
            if ($environment->deployments()->where('status', EnvironmentDeploymentStatus::RUNNING)->exists()) {
                return;
            }
            $pending = $environment->deployments()->where('status', EnvironmentDeploymentStatus::PENDING)->first();
            if ($pending) {
                $pending->status = EnvironmentDeploymentStatus::RUNNING;
                $pending->save();
                StartEnvironmentDeploymentJob::dispatch($pending->id)->afterCommit();
                app(BroadcastEnvironmentDeploymentUpdate::class)->broadcast($pending);
            } else {
                app(DeployEnvironment::class)->join($environment);
            }
        });
    }

    public function failed(int $runId): void
    {
        $run = EnvironmentDeployment::query()->find($runId);
        if ($run && $run->status->isTerminal()) {
            try {
                $run->environment->last_error = 'Old release cleanup failed. Check server connectivity.';
                $run->environment->save();
                app(BroadcastEnvironmentDeploymentUpdate::class)->broadcast($run);
            } finally {
                $this->next($run->environment);
            }
        }
    }
}
