<?php

namespace App\Policies;

use App\Models\Server;
use App\Models\User;

class ServerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->currentProject?->users->contains($user);
    }

    public function view(User $user, Server $server): bool
    {
        return $user->isAdmin() || $server->project->users->contains($user);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->currentProject?->users->contains($user);
    }

    public function update(User $user, Server $server): bool
    {
        return $user->isAdmin() || $server->project->users->contains($user);
    }

    public function delete(User $user, Server $server): bool
    {
        return $user->isAdmin() || $server->project->users->contains($user);
    }

    public function manage(User $user, Server $server): bool
    {
        return ($user->isAdmin() || $server->project->users->contains($user)) && $server->isReady();
    }
}
