<?php

namespace App\Actions\EnvironmentDeployment;

use App\Enums\ApplicationStatus;
use App\Enums\ComponentStatus;
use App\Enums\ComponentType;
use App\Enums\EnvironmentDeploymentStatus;
use App\Enums\EnvironmentStatus;
use App\Jobs\EnvironmentDeployment\StartEnvironmentDeploymentJob;
use App\Models\Application;
use App\Models\Environment;
use App\Models\EnvironmentDeployment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DeployEnvironment
{
    public function run(Environment $environment, ?User $user = null): EnvironmentDeployment
    {
        return DB::transaction(function () use ($environment, $user): EnvironmentDeployment {
            Application::query()->lockForUpdate()->findOrFail($environment->application_id);
            $environment = Environment::query()->lockForUpdate()->findOrFail($environment->id);
            $this->validate($environment);
            $running = $environment->deployments()->where('status', EnvironmentDeploymentStatus::RUNNING)->exists();
            foreach ($environment->deployments()->where('status', EnvironmentDeploymentStatus::PENDING)->get() as $pending) {
                $pending->status = EnvironmentDeploymentStatus::SUPERSEDED;
                $pending->finished_at = now();
                $pending->save();
                app(BroadcastEnvironmentDeploymentUpdate::class)->broadcast($pending);
            }
            $run = new EnvironmentDeployment;
            $run->environment_id = $environment->id;
            $run->status = $running ? EnvironmentDeploymentStatus::PENDING : EnvironmentDeploymentStatus::RUNNING;
            $run->release = (string) Str::ulid();
            $run->branch = $environment->branch;
            $run->triggered_by = $user?->id;
            $run->save();
            app(BroadcastEnvironmentDeploymentUpdate::class)->broadcast($run);
            if (! $running) {
                StartEnvironmentDeploymentJob::dispatch($run->id)->afterCommit();
            }

            return $run;
        });
    }

    public function join(Environment $environment): ?EnvironmentDeployment
    {
        return DB::transaction(function () use ($environment): ?EnvironmentDeployment {
            Application::query()->lockForUpdate()->findOrFail($environment->application_id);
            $environment = Environment::query()->lockForUpdate()->findOrFail($environment->id);
            if ($environment->status !== EnvironmentStatus::READY || $environment->application->status !== ApplicationStatus::READY
                || ! $environment->liveRun || $environment->deployments()->whereIn('status', [EnvironmentDeploymentStatus::PENDING, EnvironmentDeploymentStatus::RUNNING])->exists()
                || ! $environment->components()->where('type', ComponentType::APP)->where('status', ComponentStatus::READY)->doesntHave('deployments')->exists()) {
                return null;
            }
            $live = $environment->liveRun;
            $run = new EnvironmentDeployment;
            $run->environment_id = $environment->id;
            $run->status = EnvironmentDeploymentStatus::RUNNING;
            $run->is_join = true;
            $run->release = $live->release;
            $run->branch = $live->branch;
            $run->commit_id = $live->commit_id;
            $run->commit_data = $live->commit_data;
            $run->variables = $live->variables;
            $run->variables_version = $live->variables_version;
            $run->save();
            app(BroadcastEnvironmentDeploymentUpdate::class)->broadcast($run);
            StartEnvironmentDeploymentJob::dispatch($run->id)->afterCommit();

            return $run;
        });
    }

    public function validate(Environment $environment): void
    {
        if ($environment->status !== EnvironmentStatus::READY || $environment->application->status !== ApplicationStatus::READY) {
            throw ValidationException::withMessages(['deployment' => 'Deployments cannot start during teardown.']);
        }
        if (! $environment->components()->whereKey($environment->primary_component_id)->where('type', ComponentType::APP)->where('status', ComponentStatus::READY)->exists()) {
            throw ValidationException::withMessages(['deployment' => 'Select a ready app component as the primary before deploying.']);
        }
        if (! $environment->application->sourceControl) {
            throw ValidationException::withMessages(['deployment' => 'Select an available source control before deploying.']);
        }
    }
}
