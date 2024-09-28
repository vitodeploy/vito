<?php

namespace App\Policies;

use App\Models\DatabaseUser;
use App\Models\Server;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DatabaseUserPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user, Server $server): bool
    {
        return ($user->isAdmin() || $server->project->users->contains($user)) && $server->isReady();
    }

    public function view(User $user, DatabaseUser $databaseUser): bool
    {
        return ($user->isAdmin() || $databaseUser->server->project->users->contains($user)) &&
            $databaseUser->server->isReady();
    }

    public function create(User $user, Server $server): bool
    {
        return ($user->isAdmin() || $server->project->users->contains($user)) && $server->isReady();
    }

    public function update(User $user, DatabaseUser $databaseUser): bool
    {
        return ($user->isAdmin() || $databaseUser->server->project->users->contains($user)) &&
            $databaseUser->server->isReady();
    }

    public function delete(User $user, DatabaseUser $databaseUser): bool
    {
        return ($user->isAdmin() || $databaseUser->server->project->users->contains($user)) &&
            $databaseUser->server->isReady();
    }
}
