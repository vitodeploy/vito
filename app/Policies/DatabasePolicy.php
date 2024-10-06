<?php

namespace App\Policies;

use App\Models\Database;
use App\Models\Server;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DatabasePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user, Server $server): bool
    {
        return ($user->isAdmin() || $server->project->users->contains($user)) && $server->isReady();
    }

    public function view(User $user, Database $database): bool
    {
        return ($user->isAdmin() || $database->server->project->users->contains($user)) &&
            $database->server->isReady();
    }

    public function create(User $user, Server $server): bool
    {
        return ($user->isAdmin() || $server->project->users->contains($user)) && $server->isReady();
    }

    public function update(User $user, Database $database): bool
    {
        return ($user->isAdmin() || $database->server->project->users->contains($user)) &&
            $database->server->isReady();
    }

    public function delete(User $user, Database $database): bool
    {
        return ($user->isAdmin() || $database->server->project->users->contains($user)) &&
            $database->server->isReady();
    }
}
