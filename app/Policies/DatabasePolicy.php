<?php

namespace App\Policies;

use App\Models\Database;
use App\Models\Server;
use App\Models\User;
use App\Traits\HasRolePolicies;
use Illuminate\Auth\Access\HandlesAuthorization;

class DatabasePolicy
{
    use HandlesAuthorization;
    use HasRolePolicies;

    public function viewAny(User $user, Server $server): bool
    {
        return $this->hasReadAccess($user, $server->project)
            && $server->isReady()
            && $server->database();
    }

    public function view(User $user, Database $database): bool
    {
        $server = $database->server;

        return $this->hasReadAccess($user, $server->project) &&
            $server->isReady() &&
            $server->database();
    }

    public function create(User $user, Server $server): bool
    {
        return $this->hasWriteAccess($user, $server->project) &&
            $server->isReady() &&
            $server->database();
    }

    public function update(User $user, Database $database): bool
    {
        $server = $database->server;

        return $this->hasWriteAccess($user, $server->project) &&
            $server->isReady() &&
            $server->database();
    }

    public function delete(User $user, Database $database): bool
    {
        $server = $database->server;

        return $this->hasWriteAccess($user, $server->project) &&
            $server->isReady() &&
            $server->database();
    }
}
