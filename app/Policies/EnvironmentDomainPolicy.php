<?php

namespace App\Policies;

use App\Models\Application;
use App\Models\Environment;
use App\Models\EnvironmentDomain;
use App\Models\User;
use App\Traits\HasRolePolicies;

class EnvironmentDomainPolicy
{
    use HasRolePolicies;

    public function create(User $user, Environment $environment, Application $application): bool
    {
        return $environment->application_id === $application->id && $this->hasWriteAccess($user, $application->project);
    }

    public function update(User $user, EnvironmentDomain $domain, Environment $environment, Application $application): bool
    {
        return $domain->environment_id === $environment->id && $this->create($user, $environment, $application);
    }

    public function delete(User $user, EnvironmentDomain $domain, Environment $environment, Application $application): bool
    {
        return $domain->environment_id === $environment->id && $this->create($user, $environment, $application);
    }
}
