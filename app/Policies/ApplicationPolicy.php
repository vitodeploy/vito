<?php

namespace App\Policies;

use App\Models\Application;
use App\Models\Server;
use App\Models\User;
use App\Traits\HasRolePolicies;
use Illuminate\Auth\Access\HandlesAuthorization;

class ApplicationPolicy
{
    use HandlesAuthorization;
    use HasRolePolicies;

    public function viewAny(User $user, Server $server): bool
    {
        return $this->hasReadAccess($user, $server->project)
            && $server->isReady()
            && $server->webserver();
    }

    public function view(User $user, Application $application, Server $server): bool
    {
        return $this->hasReadAccess($user, $server->project)
            && $application->server_id === $server->id
            && $server->isReady()
            && $server->webserver();
    }

    public function create(User $user, Server $server): bool
    {
        return $this->hasWriteAccess($user, $server->project)
            && $server->isReady()
            && $server->webserver();
    }

    public function update(User $user, Application $application, Server $server): bool
    {
        return $this->hasWriteAccess($user, $server->project)
            && $application->server_id === $server->id
            && $server->isReady()
            && $server->webserver();
    }

    public function delete(User $user, Application $application, Server $server): bool
    {
        return $this->hasWriteAccess($user, $server->project)
            && $application->server_id === $server->id
            && $server->isReady()
            && $server->webserver();
    }
}
