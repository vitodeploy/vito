<?php

namespace App\Policies;

use App\Models\Application;
use App\Models\Project;
use App\Models\User;
use App\Traits\HasRolePolicies;

class ApplicationPolicy
{
    use HasRolePolicies;

    public function viewAny(User $user, Project $project): bool
    {
        return $this->hasReadAccess($user, $project);
    }

    public function view(User $user, Application $application): bool
    {
        return $this->hasReadAccess($user, $application->project);
    }

    public function create(User $user, Project $project): bool
    {
        return $this->hasWriteAccess($user, $project);
    }

    public function update(User $user, Application $application): bool
    {
        return $this->hasWriteAccess($user, $application->project);
    }

    public function delete(User $user, Application $application): bool
    {
        return $this->hasWriteAccess($user, $application->project);
    }
}
