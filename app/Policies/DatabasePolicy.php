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
        return ($user->isAdmin() || $server->project->users->contains($user))
            && $server->isReady()
            && $server->database();
    }

    public function view(User $user, Database $database): bool
    {
        return ($user->isAdmin() || $database->server->project->users->contains($user)) &&
            $database->server->isReady() &&
            $database->server->database();
    }

    public function create(User $user, Server $server): bool
    {
        return ($user->isAdmin() || $server->project->users->contains($user)) &&
            $server->isReady() &&
            $server->database();
    }

    public function update(User $user, Database $database): bool
    {
        return ($user->isAdmin() || $database->server->project->users->contains($user)) &&
            $database->server->isReady() &&
            $database->server->database();
    }

    public function delete(User $user, Database $database): bool
    {
        return ($user->isAdmin() || $database->server->project->users->contains($user)) &&
            $database->server->isReady() &&
            $database->server->database();
    }
}
