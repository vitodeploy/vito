<?php

namespace App\Actions\EnvironmentDeployment;

use App\Actions\Component\WriteComponentEnv;
use App\DTOs\GitCloneDTO;
use App\Enums\DeploymentStage;
use App\Enums\DeploymentStatus;
use App\Enums\EnvironmentDeploymentStatus;
use App\Helpers\ComponentShell;
use App\Models\Deployment;
use App\Models\Environment;
use App\Models\EnvironmentVariable;
use App\SSH\OS\Git;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RunComponentStage
{
    public function run(int $deploymentId, DeploymentStage $stage, bool $recovering = false): void
    {
        $deployment = $this->pending($deploymentId, $stage, $recovering);
        if (! $deployment) {
            return;
        }
        $deployment->log?->write($recovering ? 'Restoring previous release.' : 'Starting '.$stage->getText().'.');
        if ($recovering) {
            app(RollbackComponentRelease::class)->rollback($deployment);
        } else {
            match ($stage) {
                DeploymentStage::CLONE => $this->clone($deployment),
                DeploymentStage::RELEASE => $this->release($deployment),
                default => $this->script($deployment, $stage),
            };
        }
        $deployment->status = DeploymentStatus::FINISHED;
        $deployment->save();
        app(BroadcastEnvironmentDeploymentUpdate::class)->broadcast($deployment->environmentDeployment);
    }

    public function failed(int $deploymentId, DeploymentStage $stage, bool $recovering = false): void
    {
        $deployment = $this->pending($deploymentId, $stage, $recovering);
        if ($deployment) {
            $deployment->status = DeploymentStatus::FAILED;
            $deployment->save();
            $deployment->log?->write('The deployment stage failed. Check server connectivity and the configured script.');
            app(BroadcastEnvironmentDeploymentUpdate::class)->broadcast($deployment->environmentDeployment);
        }
    }

    private function pending(int $deploymentId, DeploymentStage $stage, bool $recovering): ?Deployment
    {
        $deployment = Deployment::query()->with('environmentDeployment', 'component.environment.application.sourceControl', 'component.server', 'component.capabilities', 'log')->find($deploymentId);
        if (! $deployment || $deployment->environmentDeployment?->status !== EnvironmentDeploymentStatus::RUNNING
            || $deployment->environmentDeployment->current_stage !== $stage || $deployment->environmentDeployment->is_recovering !== $recovering
            || $deployment->current_stage !== $stage || $deployment->status !== DeploymentStatus::DEPLOYING) {
            return null;
        }

        return $deployment;
    }

    private function clone(Deployment $deployment): void
    {
        $component = $deployment->component;
        $run = $deployment->environmentDeployment;
        $application = $component->environment->application;
        $source = $application->sourceControl ?? throw new RuntimeException('Source control is unavailable.');
        $provider = $source->provider();
        $key = 'component_'.$component->id;
        $url = $provider->fullRepoUrl($application->repository, $key);
        if (! $source->isGithubApp() && preg_match('/\A[a-zA-Z0-9_.]+@[a-zA-Z0-9_.-]+:.*\z/', $url) !== 1) {
            throw new RuntimeException('Invalid SSH repository address.');
        }
        app(Git::class)->cloneRepository(new GitCloneDTO(
            server: $component->server, user: $component->user, repoUrl: $url, branch: $run->branch,
            path: $deployment->path(), keyName: $key, port: $provider->getSshPort(), usesToken: $source->isGithubApp(),
            variables: $source->cloneVariables(), commit: $run->commit_id, log: $deployment->log,
        ));
        DB::transaction(function () use ($component, $run): void {
            $environment = Environment::query()->lockForUpdate()->findOrFail($component->environment_id);
            $component->needs_redeploy = $environment->variables_version !== $run->variables_version;
            $component->save();
        });
        $component->environment->setRelation('variables', new Collection(array_map(
            fn (array $variable): EnvironmentVariable => new EnvironmentVariable($variable), $run->variables,
        )));
        app(WriteComponentEnv::class)->write($component, $component->envPath().'.next');
        $component->server->ssh($component->user)->setLog($deployment->log)->exec(view('ssh.applications.link-shared', [
            'component' => $component, 'releasePath' => $deployment->path(), 'paths' => $component->environment->shared_paths ?? [],
        ]));
    }

    private function script(Deployment $deployment, DeploymentStage $stage): void
    {
        $component = $deployment->component;
        $script = $component->deploymentScripts()->where('name', $stage->value)->first()
            ?? $component->environment->deploymentScripts()->where('name', $stage->value)->first();
        if (! $script || trim($script->content) === '') {
            return;
        }
        $command = "set -e\nset -o pipefail\n";
        foreach (['COMMIT_ID' => $deployment->commit_id, 'RELEASE_PATH' => $deployment->path(), 'BRANCH' => $deployment->environmentDeployment->branch] as $key => $value) {
            $command .= 'export '.$key.'='.escapeshellarg($value)."\n";
        }
        $command .= $script->content;
        $component->server->os()->runScript(
            $deployment->path(),
            ComponentShell::wrap($component, $command, $component->envPath().($stage === DeploymentStage::POST_RELEASE ? '' : '.next'), $deployment->path()),
            $deployment->log, $component->user,
        );
    }

    private function release(Deployment $deployment): void
    {
        $component = $deployment->component;
        $deployment->release_started = true;
        $deployment->save();
        $component->server->ssh($component->user)->setLog($deployment->log)->exec(view('ssh.applications.release-env', ['component' => $component, 'deployment' => $deployment]));
        $component->server->ssh($component->user)->setLog($deployment->log)->exec(view('ssh.modern-deployment.release', [
            'basePath' => $component->basePath(), 'releasePath' => $deployment->path(),
        ]));
        $deployment->activate();
        foreach ($component->capabilities as $capability) {
            $capability->handler()->afterRelease($component, $deployment);
        }
    }
}
