<?php

namespace App\Policies;

use App\Models\Application;
use App\Models\Environment;
use App\Models\User;
use App\Traits\HasRolePolicies;

class EnvironmentPolicy
{
    use HasRolePolicies;

    public function viewAny(User $user, Application $application): bool
    {
        return $this->hasReadAccess($user, $application->project);
    }

    public function view(User $user, Environment $environment, Application $application): bool
    {
        return $environment->application_id === $application->id
            && $this->hasReadAccess($user, $application->project);
    }

    public function create(User $user, Application $application): bool
    {
        return $this->hasWriteAccess($user, $application->project);
    }

    public function update(User $user, Environment $environment, Application $application): bool
    {
        return $environment->application_id === $application->id
            && $this->hasWriteAccess($user, $application->project);
    }

    public function delete(User $user, Environment $environment, Application $application): bool
    {
        return $environment->application_id === $application->id
            && $this->hasWriteAccess($user, $application->project);
    }
}
