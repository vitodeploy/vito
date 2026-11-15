<?php

namespace App\Policies;

use App\Models\Application;
use App\Models\Component;
use App\Models\Environment;
use App\Models\User;
use App\Traits\HasRolePolicies;

class ComponentPolicy
{
    use HasRolePolicies;

    public function view(User $user, Component $component, Environment $environment, Application $application): bool
    {
        return $component->environment_id === $environment->id
            && $environment->application_id === $application->id
            && $this->hasReadAccess($user, $application->project);
    }

    public function create(User $user, Environment $environment, Application $application): bool
    {
        return $environment->application_id === $application->id
            && $this->hasWriteAccess($user, $application->project);
    }

    public function update(User $user, Component $component, Environment $environment, Application $application): bool
    {
        return $component->environment_id === $environment->id
            && $environment->application_id === $application->id
            && $this->hasWriteAccess($user, $application->project);
    }

    public function delete(User $user, Component $component, Environment $environment, Application $application): bool
    {
        return $component->environment_id === $environment->id
            && $environment->application_id === $application->id
            && $this->hasWriteAccess($user, $application->project);
    }
}
