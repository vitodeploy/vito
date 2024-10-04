<?php

namespace App\Policies;

use App\Models\Server;
use App\Models\ServerLog;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ServerLogPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user, Server $server): bool
    {
        return $user->isAdmin() || $server->project->users->contains($user);
    }

    public function view(User $user, ServerLog $serverLog): bool
    {
        return $user->isAdmin() || $serverLog->server->project->users->contains($user);
    }

    public function create(User $user, Server $server): bool
    {
        return $user->isAdmin() || $server->project->users->contains($user);
    }

    public function update(User $user, ServerLog $serverLog): bool
    {
        return $user->isAdmin() || $serverLog->server->project->users->contains($user);
    }

    public function delete(User $user, ServerLog $serverLog): bool
    {
        return $user->isAdmin() || $serverLog->server->project->users->contains($user);
    }

    public function deleteMany(User $user, Server $server): bool
    {
        return $user->isAdmin() || $server->project->users->contains($user);
    }
}
