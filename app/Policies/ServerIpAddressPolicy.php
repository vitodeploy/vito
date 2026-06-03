<?php

namespace App\Policies;

use App\Models\Server;
use App\Models\ServerIpAddress;
use App\Models\User;
use App\Traits\HasRolePolicies;
use Illuminate\Auth\Access\HandlesAuthorization;

class ServerIpAddressPolicy
{
    use HandlesAuthorization;
    use HasRolePolicies;

    public function viewAny(User $user, Server $server): bool
    {
        return $this->hasReadAccess($user, $server->project)
            && $server->isReady();
    }

    public function create(User $user, Server $server): bool
    {
        return $this->hasWriteAccess($user, $server->project)
            && $server->isReady();
    }

    public function update(User $user, ServerIpAddress $address): bool
    {
        $server = $address->server;

        return $this->hasWriteAccess($user, $server->project)
            && $server->isReady();
    }

    public function delete(User $user, ServerIpAddress $address): bool
    {
        $server = $address->server;

        return $this->hasWriteAccess($user, $server->project)
            && $server->isReady();
    }
}
