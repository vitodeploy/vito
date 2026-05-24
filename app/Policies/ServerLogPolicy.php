<?php

namespace App\Policies;

use App\Models\Server;
use App\Models\ServerLog;
use App\Models\User;
use App\Traits\HasRolePolicies;
use Illuminate\Auth\Access\HandlesAuthorization;

class ServerLogPolicy
{
    use HandlesAuthorization;
    use HasRolePolicies;

    public function viewAny(User $user, Server $server): bool
    {
        return $this->hasReadAccess($user, $server->project);
    }

    public function view(User $user, ServerLog $serverLog): bool
    {
        $server = $serverLog->server;

        return $this->hasReadAccess($user, $server->project);
    }

    public function create(User $user, Server $server): bool
    {
        return $this->hasWriteAccess($user, $server->project);
    }

    public function update(User $user, ServerLog $serverLog): bool
    {
        $server = $serverLog->server;

        return $this->hasWriteAccess($user, $server->project);
    }

    public function delete(User $user, ServerLog $serverLog): bool
    {
        $server = $serverLog->server;

        return $this->hasWriteAccess($user, $server->project);
    }

    public function deleteMany(User $user, Server $server): bool
    {
        return $this->hasWriteAccess($user, $server->project);
    }
}
