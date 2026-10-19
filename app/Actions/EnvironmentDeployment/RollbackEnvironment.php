<?php

namespace App\Actions\EnvironmentDeployment;

use App\Enums\ApplicationStatus;
use App\Enums\ComponentType;
use App\Enums\EnvironmentStatus;
use App\Models\Component;
use App\Models\Environment;
use App\Models\EnvironmentDeployment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class RollbackEnvironment
{
    public function run(Environment $environment, EnvironmentDeployment $target, ?User $user = null): EnvironmentDeployment
    {
        return app(DeployEnvironment::class)->run($environment, $user, $target);
    }

    /** @return Builder<Component> */
    public function components(Environment $environment): Builder
    {
        return $environment->components()->getQuery()->where('type', ComponentType::APP)->has('deployments');
    }

    public function validate(Environment $environment, EnvironmentDeployment $target): void
    {
        if ($environment->status !== EnvironmentStatus::READY || $environment->application->status !== ApplicationStatus::READY
            || $target->environment_id !== $environment->id || ! $target->canRollback()) {
            throw ValidationException::withMessages(['deployment' => 'Choose a finished non-join deployment whose release is retained on every deployed app component.']);
        }
    }

    public function checkReleases(EnvironmentDeployment $run): void
    {
        $environment = $run->environment;
        $this->validate($environment, $run->rollbackOf);
        foreach ($this->components($environment)->with('server')->get() as $component) {
            $component->server->ssh($component->user)->exec('test -d '.escapeshellarg($component->releasesPath().'/'.$run->release));
        }
    }
}
