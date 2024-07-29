<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::ADMIN;
    }

    public function view(User $user, Project $project): bool
    {
        return $user->role === UserRole::ADMIN || $project->users->contains($user);
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::ADMIN;
    }

    public function update(User $user, Project $project): bool
    {
        return $user->role === UserRole::ADMIN;
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->role === UserRole::ADMIN;
    }
}
