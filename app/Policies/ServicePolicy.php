<?php

namespace App\Policies;

use App\Models\Server;
use App\Models\Service;
use App\Models\User;
use App\Traits\HasRolePolicies;
use Illuminate\Auth\Access\HandlesAuthorization;

class ServicePolicy
{
    use HandlesAuthorization;
    use HasRolePolicies;

    public function viewAny(User $user, Server $server): bool
    {
        return $this->hasReadAccess($user, $server->project) && $server->isReady();
    }

    public function view(User $user, Service $service): bool
    {
        $server = $service->server;

        return $this->hasReadAccess($user, $server->project) && $server->isReady();
    }

    public function create(User $user, Server $server): bool
    {
        return $this->hasWriteAccess($user, $server->project) && $server->isReady();
    }

    public function update(User $user, Service $service): bool
    {
        $server = $service->server;

        return $this->hasWriteAccess($user, $server->project) && $server->isReady();
    }

    public function delete(User $user, Service $service): bool
    {
        $server = $service->server;

        return $this->hasWriteAccess($user, $server->project) && $server->isReady();
    }

    public function start(User $user, Service $service): bool
    {
        return $this->update($user, $service);
    }

    public function stop(User $user, Service $service): bool
    {
        return $this->update($user, $service);
    }

    public function restart(User $user, Service $service): bool
    {
        return $this->update($user, $service);
    }

    public function reload(User $user, Service $service): bool
    {
        return $this->update($user, $service);
    }

    public function disable(User $user, Service $service): bool
    {
        return $this->update($user, $service);
    }

    public function enable(User $user, Service $service): bool
    {
        return $this->update($user, $service);
    }
}
