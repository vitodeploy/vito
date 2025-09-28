<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use App\Traits\HasRolePolicies;

class ProjectPolicy
{
    use HasRolePolicies;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Project $project): bool
    {
        return $this->hasReadAccess($user, $project);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Project $project): bool
    {
        return $this->hasWriteAccess($user, $project);
    }

    public function delete(User $user, Project $project): bool
    {
        return $this->hasOwnerAccess($user, $project);
    }

    public function deleteUser(User $user, Project $project): bool
    {
        return $this->hasOwnerAccess($user, $project);
    }
}
