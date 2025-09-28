<?php

namespace App\Policies;

use App\Models\Server;
use App\Models\Site;
use App\Models\User;
use App\Models\Worker;
use App\Traits\HasRolePolicies;
use Illuminate\Auth\Access\HandlesAuthorization;

class WorkerPolicy
{
    use HandlesAuthorization;
    use HasRolePolicies;

    public function viewAny(User $user, Server $server, ?Site $site = null): bool
    {
        return $this->hasReadAccess($user, $server->project) &&
            $server->isReady() &&
            $server->processManager();
    }

    public function view(User $user, Worker $worker, Server $server, ?Site $site = null): bool
    {
        return $this->hasReadAccess($user, $server->project) &&
            $server->isReady() &&
            $worker->server_id === $server->id &&
            $server->processManager();
    }

    public function create(User $user, Server $server, ?Site $site = null): bool
    {
        return $this->hasWriteAccess($user, $server->project) &&
            $server->isReady() &&
            $server->processManager();
    }

    public function update(User $user, Worker $worker, Server $server, ?Site $site = null): bool
    {
        return $this->hasWriteAccess($user, $server->project) &&
            $server->isReady() &&
            $worker->server_id === $server->id &&
            $server->processManager();
    }

    public function delete(User $user, Worker $worker, Server $server, ?Site $site = null): bool
    {
        return $this->hasWriteAccess($user, $server->project) &&
            $server->isReady() &&
            $worker->server_id === $server->id &&
            $server->processManager();
    }
}
