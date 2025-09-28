<?php

namespace App\Policies;

use App\Models\DatabaseUser;
use App\Models\Server;
use App\Models\User;
use App\Traits\HasRolePolicies;
use Illuminate\Auth\Access\HandlesAuthorization;

class DatabaseUserPolicy
{
    use HandlesAuthorization;
    use HasRolePolicies;

    public function viewAny(User $user, Server $server): bool
    {
        return $this->hasReadAccess($user, $server->project)
            && $server->isReady()
            && $server->database();
    }

    public function view(User $user, DatabaseUser $databaseUser): bool
    {
        $server = $databaseUser->server;

        return $this->hasReadAccess($user, $server->project) &&
            $server->isReady()
            && $server->database();
    }

    public function create(User $user, Server $server): bool
    {
        return $this->hasWriteAccess($user, $server->project)
            && $server->isReady()
            && $server->database();
    }

    public function update(User $user, DatabaseUser $databaseUser): bool
    {
        $server = $databaseUser->server;

        return $this->hasWriteAccess($user, $server->project) &&
            $server->isReady()
            && $server->database();
    }

    public function delete(User $user, DatabaseUser $databaseUser): bool
    {
        $server = $databaseUser->server;

        return $this->hasWriteAccess($user, $server->project) &&
            $server->isReady()
            && $server->database();
    }
}
