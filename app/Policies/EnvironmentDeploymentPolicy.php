<?php

namespace App\Policies;

use App\Models\Application;
use App\Models\Environment;
use App\Models\EnvironmentDeployment;
use App\Models\User;
use App\Traits\HasRolePolicies;

class EnvironmentDeploymentPolicy
{
    use HasRolePolicies;

    public function viewAny(User $user, Environment $environment, Application $application): bool
    {
        return $environment->application_id === $application->id && $this->hasReadAccess($user, $application->project);
    }

    public function view(User $user, EnvironmentDeployment $deployment, Environment $environment, Application $application): bool
    {
        return $deployment->environment_id === $environment->id && $this->viewAny($user, $environment, $application);
    }

    public function create(User $user, Environment $environment, Application $application): bool
    {
        return $environment->application_id === $application->id && $this->hasWriteAccess($user, $application->project);
    }
}
