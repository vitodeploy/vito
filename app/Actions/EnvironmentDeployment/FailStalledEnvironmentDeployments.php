<?php

namespace App\Actions\EnvironmentDeployment;

use App\Enums\DeploymentStage;
use App\Enums\DeploymentStatus;
use App\Enums\EnvironmentDeploymentStatus;
use App\Models\Application;
use App\Models\Environment;
use App\Models\EnvironmentDeployment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class FailStalledEnvironmentDeployments
{
    public function handle(): void
    {
        EnvironmentDeployment::query()->blocking()->where('status', '!=', EnvironmentDeploymentStatus::PENDING)
            ->where(fn ($query) => $query->where('stage_deadline_at', '<=', now())
                ->orWhere(fn ($query) => $query->whereNull('stage_deadline_at')->where('updated_at', '<=', now()->subSeconds(DeploymentStage::CLONE->timeout()))))
            ->each(fn (EnvironmentDeployment $run) => $this->fail($run));

        Environment::query()->whereHas('deployments', fn ($query) => $query->where('status', EnvironmentDeploymentStatus::PENDING))
            ->each(fn (Environment $environment) => app(FinishEnvironmentDeployment::class)->next($environment));
    }

    private function fail(EnvironmentDeployment $run): void
    {
        $locks = [];
        try {
            foreach ($run->deployments()->orderBy('component_id')->pluck('component_id') as $componentId) {
                $lock = Cache::lock('unique-queue:component-'.$componentId, 60);
                if (! $lock->get()) {
                    return;
                }
                $locks[] = $lock;
            }
            DB::transaction(function () use ($run): void {
                Application::query()->lockForUpdate()->findOrFail($run->environment->application_id);
                Environment::query()->lockForUpdate()->findOrFail($run->environment_id);
                $current = EnvironmentDeployment::query()->lockForUpdate()->findOrFail($run->id);
                if (! in_array($current->status, [EnvironmentDeploymentStatus::RUNNING, EnvironmentDeploymentStatus::CANCELLED], true)
                    || $current->current_stage !== $run->current_stage || $current->is_recovering !== $run->is_recovering
                    || ($current->stage_deadline_at && $current->stage_deadline_at->isFuture())) {
                    return;
                }
                $current->last_error = 'Deployment deadline exceeded. Check queue workers, server connectivity and component logs.';
                $current->save();
                if (! $current->current_stage) {
                    app(AdvanceEnvironmentDeployment::class)->finish($current, EnvironmentDeploymentStatus::FAILED);

                    return;
                }
                foreach (app(AdvanceEnvironmentDeployment::class)->participants($current, $current->current_stage)->where('status', DeploymentStatus::DEPLOYING)->get() as $deployment) {
                    $deployment->status = DeploymentStatus::FAILED;
                    $deployment->save();
                    $deployment->log?->write('The deployment stage exceeded its deadline.');
                }
            });
        } finally {
            foreach ($locks as $lock) {
                $lock->release();
            }
        }
        if ($run->current_stage) {
            app(AdvanceEnvironmentDeployment::class)->advance($run->id, $run->current_stage, $run->is_recovering);
        }
    }
}
