<?php

namespace App\Actions\EnvironmentDeployment;

use App\Enums\ComponentType;
use App\Enums\DeploymentStage;
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
        if (! $run || ! $run->status->isTerminal() || $run->stage_deadline_at) {
            return;
        }
        try {
            foreach ($run->deployments()->whereHas('component', fn ($query) => $query->where('type', ComponentType::APP))->with('component.environment', 'component.server')->get() as $deployment) {
                if (Cache::lock('unique-queue:component-'.$deployment->component_id, 1260)->get(fn () => DB::transaction(function () use ($run, $deployment): void {
                    Application::query()->lockForUpdate()->findOrFail($run->environment->application_id);
                    Environment::query()->lockForUpdate()->findOrFail($run->environment_id);
                    $deployment->refresh()->pruneOldReleases();
                })) === false) {
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
            if ($environment->deployments()->blocking()->where('status', '!=', EnvironmentDeploymentStatus::PENDING)->exists()) {
                return;
            }
            $pending = $environment->deployments()->where('status', EnvironmentDeploymentStatus::PENDING)->first();
            if ($pending) {
                $pending->status = EnvironmentDeploymentStatus::RUNNING;
                $pending->stage_deadline_at = now()->addSeconds(DeploymentStage::CLONE->timeout());
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
