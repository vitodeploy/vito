<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;

class ServerPolicy
{
    public function viewAny(User $user, Project $project): bool
    {
        return $user->role === UserRole::ADMIN || $project->users->contains($user);
    }

    public function view(User $user, Server $server): bool
    {
        return $user->role === UserRole::ADMIN || $server->project->users->contains($user);
    }

    public function create(User $user, Project $project): bool
    {
        return $user->role === UserRole::ADMIN || $project->users->contains($user);
    }

    public function update(User $user, Server $server): bool
    {
        return $user->role === UserRole::ADMIN || $server->project->users->contains($user);
    }

    public function delete(User $user, Server $server): bool
    {
        return $user->role === UserRole::ADMIN || $server->project->users->contains($user);
    }

    public function manage(User $user, Server $server): bool
    {
        return $user->role === UserRole::ADMIN || $server->project->users->contains($user);
    }
}
