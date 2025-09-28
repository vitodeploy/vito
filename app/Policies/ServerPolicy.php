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
        return $project->hasRoles($user, UserRole::cases());
    }

    public function view(User $user, Server $server): bool
    {
        return $server->project->hasRoles($user, UserRole::cases());
    }

    public function create(User $user, Project $project): bool
    {
        return $project->hasRoles($user, [
            UserRole::OWNER,
            UserRole::ADMIN,
        ]);
    }

    public function update(User $user, Server $server): bool
    {
        return $server->project->hasRoles($user, [
            UserRole::OWNER,
            UserRole::ADMIN,
        ]);
    }

    public function delete(User $user, Server $server): bool
    {
        return $server->project->hasRoles($user, [
            UserRole::OWNER,
        ]);
    }

    public function manage(User $user, Server $server): bool
    {
        return $server->isReady() && $this->update($user, $server);
    }
}
