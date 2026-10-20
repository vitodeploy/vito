<?php

namespace App\Actions\EnvironmentDeployment;

use App\Actions\Application\GetApplicationSourceControl;
use App\Actions\EnvironmentAttachment\ValidateEnvironmentResource;
use App\Enums\ApplicationStatus;
use App\Enums\ComponentStatus;
use App\Enums\ComponentType;
use App\Enums\DeploymentStage;
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
    public function run(Environment $environment, ?User $user = null, ?EnvironmentDeployment $rollbackOf = null): EnvironmentDeployment
    {
        return DB::transaction(function () use ($environment, $user, $rollbackOf): EnvironmentDeployment {
            Application::query()->lockForUpdate()->findOrFail($environment->application_id);
            $environment = Environment::query()->lockForUpdate()->findOrFail($environment->id);
            if ($rollbackOf) {
                $rollbackOf = EnvironmentDeployment::query()->findOrFail($rollbackOf->id);
                app(RollbackEnvironment::class)->validate($environment, $rollbackOf);
            } else {
                $this->validate($environment);
            }
            $running = $environment->deployments()->blocking()->where('status', '!=', EnvironmentDeploymentStatus::PENDING)->exists();
            foreach ($environment->deployments()->where('status', EnvironmentDeploymentStatus::PENDING)->get() as $pending) {
                $pending->status = EnvironmentDeploymentStatus::SUPERSEDED;
                $pending->finished_at = now();
                $pending->save();
                app(BroadcastEnvironmentDeploymentUpdate::class)->broadcast($pending);
            }
            $run = new EnvironmentDeployment;
            $run->environment_id = $environment->id;
            $run->status = $running ? EnvironmentDeploymentStatus::PENDING : EnvironmentDeploymentStatus::RUNNING;
            $run->release = $rollbackOf->release ?? (string) Str::ulid();
            $run->rollback_of_id = $rollbackOf?->id;
            $run->commit_id = $rollbackOf?->commit_id;
            $run->commit_data = $rollbackOf?->commit_data;
            $run->stage_deadline_at = $running ? null : now()->addSeconds(DeploymentStage::CLONE->timeout());
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
                || ! $environment->liveRun || $environment->deployments()->blocking()->exists()
                || ! app(ValidateEnvironmentResource::class)->ready($environment)
                || ($environment->liveRun->attachment_ids ?? []) !== $environment->attachments()->orderBy('id')->pluck('id')->all()
                || ! $environment->components()->where('type', ComponentType::APP)->where('status', ComponentStatus::READY)->doesntHave('deployments')->exists()) {
                return null;
            }
            $live = $environment->liveRun;
            $run = new EnvironmentDeployment;
            $run->environment_id = $environment->id;
            $run->status = EnvironmentDeploymentStatus::RUNNING;
            $run->is_join = true;
            $run->stage_deadline_at = now()->addSeconds(DeploymentStage::CLONE->timeout());
            $run->release = $live->release;
            $run->branch = $live->branch;
            $run->commit_id = $live->commit_id;
            $run->commit_data = $live->commit_data;
            $run->attachment_ids = $live->attachment_ids ?? [];
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
        if (! app(ValidateEnvironmentResource::class)->ready($environment)) {
            throw ValidationException::withMessages(['deployment' => 'Wait for all resources to finish synchronizing or detaching before deploying.']);
        }
        app(GetApplicationSourceControl::class)->get($environment->application);
    }
}
