<?php

namespace App\Actions\EnvironmentDeployment;

use App\Enums\DeploymentStage;
use App\Enums\DeploymentStatus;
use App\Enums\EnvironmentDeploymentStatus;
use App\Jobs\EnvironmentDeployment\DeployComponentStageJob;
use App\Jobs\EnvironmentDeployment\FinishEnvironmentDeploymentJob;
use App\Models\Application;
use App\Models\Component;
use App\Models\Deployment;
use App\Models\Environment;
use App\Models\EnvironmentDeployment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class AdvanceEnvironmentDeployment
{
    public function advance(int $runId, DeploymentStage $stage, bool $recovering = false): void
    {
        $run = EnvironmentDeployment::query()->find($runId);
        if (! $run) {
            return;
        }
        DB::transaction(function () use ($run, $stage, $recovering): void {
            Application::query()->lockForUpdate()->findOrFail($run->environment->application_id);
            $environment = Environment::query()->lockForUpdate()->findOrFail($run->environment_id);
            $run = EnvironmentDeployment::query()->lockForUpdate()->findOrFail($run->id);
            if (! in_array($run->status, [EnvironmentDeploymentStatus::RUNNING, EnvironmentDeploymentStatus::CANCELLED], true) || $run->current_stage !== $stage || $run->is_recovering !== $recovering) {
                return;
            }
            $participants = $this->participants($run, $stage)->get();
            if ($participants->contains('status', DeploymentStatus::DEPLOYING)) {
                return;
            }
            if ($run->status === EnvironmentDeploymentStatus::CANCELLED) {
                if ($run->stage_deadline_at) {
                    $this->finish($run, EnvironmentDeploymentStatus::CANCELLED);
                }

                return;
            }
            $failed = $participants->contains('status', DeploymentStatus::FAILED);
            if ($recovering) {
                $this->finish($run, $failed ? EnvironmentDeploymentStatus::PARTIAL : EnvironmentDeploymentStatus::ROLLED_BACK);

                return;
            }
            if ($failed) {
                if ($stage === DeploymentStage::RELEASE) {
                    $run->is_recovering = true;
                    $run->save();
                    $this->dispatch($run, $stage);
                } else {
                    $this->finish($run, EnvironmentDeploymentStatus::FAILED);
                }

                return;
            }
            if ($stage === DeploymentStage::RELEASE && ! $run->is_join) {
                $environment->live_run_id = $run->id;
                $environment->save();
            }
            $next = $run->rollback_of_id ? null : $stage->next();
            if ($run->is_join && $next === DeploymentStage::PRE_RELEASE) {
                $next = DeploymentStage::RELEASE;
            }
            if ($next) {
                $this->dispatch($run, $next);
            } else {
                $this->finish($run, EnvironmentDeploymentStatus::FINISHED);
            }
        });
    }

    public function failed(int $runId): void
    {
        $run = EnvironmentDeployment::query()->find($runId);
        if ($run && $run->status === EnvironmentDeploymentStatus::RUNNING) {
            $run->environment->last_error = 'Deployment could not advance. Check the queue workers and component logs.';
            $run->environment->save();
            app(BroadcastEnvironmentDeploymentUpdate::class)->broadcast($run);
        }
    }

    /** @return Builder<Deployment> */
    public function participants(EnvironmentDeployment $run, DeploymentStage $stage): Builder
    {
        return $run->deployments()->getQuery()
            ->whereHas('component', fn ($query) => $query->whereIn('type', $stage === DeploymentStage::RELEASE && ! $run->is_join
                ? [\App\Enums\ComponentType::APP, \App\Enums\ComponentType::LOAD_BALANCER] : [\App\Enums\ComponentType::APP]))
            ->when($stage === DeploymentStage::PRE_RELEASE, fn ($query) => $query->where('component_id', $run->primary_component_id));
    }

    public function dispatch(EnvironmentDeployment $run, DeploymentStage $stage): void
    {
        $run->current_stage = $stage;
        $run->stage_deadline_at = now()->addSeconds($stage->timeout());
        $run->save();
        foreach ($this->participants($run, $stage)->get() as $deployment) {
            $deployment->current_stage = $stage;
            $deployment->status = DeploymentStatus::DEPLOYING;
            $deployment->save();
            DeployComponentStageJob::dispatch($deployment->id, $deployment->component_id, $run->id, $stage, $run->stage_deadline_at->timestamp, $run->is_recovering)->afterCommit();
        }
        app(BroadcastEnvironmentDeploymentUpdate::class)->broadcast($run);
    }

    public function finish(EnvironmentDeployment $run, EnvironmentDeploymentStatus $status): void
    {
        if ($run->is_join && ! $run->deployments()->exists()) {
            foreach ($run->environment->components()->where('type', \App\Enums\ComponentType::APP)->where('status', \App\Enums\ComponentStatus::READY)->doesntHave('deployments')->get() as $component) {
                $run->deployments()->create([
                    'component_id' => $component->id, 'commit_id' => $run->commit_id,
                    'commit_data' => $run->commit_data, 'status' => DeploymentStatus::FAILED,
                ]);
            }
        }
        $run->status = $status;
        $run->finished_at = now();
        $draining = $status === EnvironmentDeploymentStatus::CANCELLED && $run->deployments()->where('status', DeploymentStatus::DEPLOYING)->exists();
        if (! $draining) {
            $run->stage_deadline_at = null;
        }
        $run->save();
        if ($status !== EnvironmentDeploymentStatus::FINISHED) {
            Component::query()->where('type', \App\Enums\ComponentType::APP)->whereIn('id', $run->deployments()->select('component_id'))->update(['needs_redeploy' => true]);
        }
        $environment = $run->environment;
        $environment->last_error = $status === EnvironmentDeploymentStatus::FINISHED ? null : 'Deployment did not finish successfully. Review its component stages and logs before deploying again.';
        $environment->save();
        app(BroadcastEnvironmentDeploymentUpdate::class)->broadcast($run);
        if (! $draining) {
            FinishEnvironmentDeploymentJob::dispatch($run->id)->afterCommit();
        }
    }
}
