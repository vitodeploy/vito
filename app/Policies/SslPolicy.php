<?php

namespace App\Policies;

use App\Models\Server;
use App\Models\Ssl;
use App\Models\User;
use App\Traits\HasRolePolicies;
use Illuminate\Auth\Access\HandlesAuthorization;

class SslPolicy
{
    use HandlesAuthorization;
    use HasRolePolicies;

    public function viewAny(User $user, Server $server): bool
    {
        return $this->hasReadAccess($user, $server->project) &&
            $server->isReady();
    }

    public function create(User $user, Server $server): bool
    {
        return $this->hasWriteAccess($user, $server->project) &&
            $server->isReady();
    }

    public function downloadCsr(User $user, Ssl $ssl, Server $server): bool
    {
        return $this->hasReadAccess($user, $server->project) &&
            $server->isReady() &&
            $ssl->server_id === $server->id;
    }

    public function delete(User $user, Ssl $ssl, Server $server): bool
    {
        return $this->hasWriteAccess($user, $server->project) &&
            $server->isReady() &&
            $ssl->server_id === $server->id;
    }

    public function activate(User $user, Ssl $ssl, Server $server): bool
    {
        return $this->hasWriteAccess($user, $server->project) &&
            $server->isReady() &&
            $ssl->server_id === $server->id;
    }
}
