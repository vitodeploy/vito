<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Project $project): bool
    {
        return $project->hasRoles($user, [UserRole::OWNER, UserRole::ADMIN, UserRole::USER]);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Project $project): bool
    {
        return $project->hasRoles($user, [UserRole::OWNER, UserRole::ADMIN]);
    }

    public function delete(User $user, Project $project): bool
    {
        return $project->hasRoles($user, [UserRole::OWNER]);
    }

    public function deleteUser(User $user, Project $project): bool
    {
        return $project->hasRoles($user, [UserRole::OWNER]);
    }
}
