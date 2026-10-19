<?php

namespace App\Actions\EnvironmentDeployment;

use App\Enums\ComponentStatus;
use App\Enums\ComponentType;
use App\Enums\DeploymentStage;
use App\Enums\DeploymentStatus;
use App\Enums\EnvironmentDeploymentStatus;
use App\Models\Application;
use App\Models\Deployment;
use App\Models\Environment;
use App\Models\EnvironmentDeployment;
use App\Models\EnvironmentVariable;
use App\Models\ServerLog;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StartEnvironmentDeployment
{
    public function start(int $runId): void
    {
        $run = EnvironmentDeployment::query()->with('environment.application.sourceControl')->find($runId);
        if (! $run || $run->status !== EnvironmentDeploymentStatus::RUNNING || $run->current_stage !== null) {
            return;
        }
        if ($run->stage_deadline_at?->isPast()) {
            $this->failed($runId);

            return;
        }
        $environment = $run->environment;
        $commit = ($run->is_join || $run->rollback_of_id) ? ['commit_id' => $run->commit_id, 'commit_data' => $run->commit_data]
            : $environment->application->sourceControl?->provider()->getLastCommit($environment->application->repository, $environment->branch);
        if (! is_string($commit['commit_id'] ?? null) || preg_match('/\A(?:[a-f0-9]{40}|[a-f0-9]{64})\z/i', $commit['commit_id']) !== 1) {
            throw new RuntimeException('Unable to resolve a full commit SHA for this deployment.');
        }
        if ($run->rollback_of_id) {
            app(RollbackEnvironment::class)->checkReleases($run);
        }
        DB::transaction(function () use ($run, $environment, $commit): void {
            Application::query()->lockForUpdate()->findOrFail($environment->application_id);
            $environment = Environment::query()->lockForUpdate()->findOrFail($environment->id);
            $run = EnvironmentDeployment::query()->lockForUpdate()->findOrFail($run->id);
            if ($run->status !== EnvironmentDeploymentStatus::RUNNING || $run->current_stage !== null) {
                return;
            }
            if ($run->rollback_of_id) {
                app(RollbackEnvironment::class)->validate($environment, $run->rollbackOf);
                $run->branch = $run->rollbackOf->branch;
                $run->variables = $environment->liveRun->variables ?? [];
                $run->variables_version = $environment->liveRun->variables_version ?? 0;
            } elseif (! $run->is_join) {
                app(DeployEnvironment::class)->validate($environment);
                $run->primary_component_id = $environment->primary_component_id;
                $run->branch = $environment->branch;
                $run->variables = $environment->variables()->get()->map(fn (EnvironmentVariable $variable): array => [
                    'key' => $variable->key, 'value' => $variable->value, 'is_secret' => $variable->is_secret,
                ])->all();
                $run->variables_version = $environment->variables_version;
            }
            $run->commit_id = $commit['commit_id'];
            $run->commit_data = $commit['commit_data'] ?? [];
            $run->started_at = now();
            $run->save();
            $components = $environment->components()->where('type', ComponentType::APP)->where('status', ComponentStatus::READY)
                ->when($run->is_join, fn ($query) => $query->doesntHave('deployments'))
                ->when($run->rollback_of_id, fn ($query) => $query->has('deployments'))->with('server')->get();
            foreach ($components as $component) {
                $log = ServerLog::newLog($component->server, 'component-'.$component->id.'-deployment-'.$run->id);
                $log->save();
                $deployment = new Deployment([
                    'component_id' => $component->id, 'log_id' => $log->id, 'commit_id' => $run->commit_id,
                    'commit_data' => $run->commit_data, 'release' => $run->release, 'status' => DeploymentStatus::DEPLOYING,
                ]);
                $deployment->environment_deployment_id = $run->id;
                $deployment->previous_deployment_id = $component->deployments()->where('active', true)->value('id');
                $deployment->save();
            }
            if ($components->isEmpty()) {
                app(AdvanceEnvironmentDeployment::class)->finish($run, EnvironmentDeploymentStatus::FAILED);

                return;
            }
            app(AdvanceEnvironmentDeployment::class)->dispatch($run, $run->rollback_of_id ? DeploymentStage::RELEASE : DeploymentStage::CLONE);
        });
    }

    public function failed(int $runId): void
    {
        $run = EnvironmentDeployment::query()->find($runId);
        if (! $run) {
            return;
        }
        DB::transaction(function () use ($run): void {
            Application::query()->lockForUpdate()->findOrFail($run->environment->application_id);
            Environment::query()->lockForUpdate()->findOrFail($run->environment_id);
            $run = EnvironmentDeployment::query()->lockForUpdate()->findOrFail($run->id);
            if ($run->status === EnvironmentDeploymentStatus::RUNNING && $run->current_stage === null) {
                $run->last_error = 'Deployment could not start. Check repository access, component readiness and release availability.';
                app(AdvanceEnvironmentDeployment::class)->finish($run, EnvironmentDeploymentStatus::FAILED);
            }
        });
    }
}
