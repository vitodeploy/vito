<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use App\Traits\HasRolePolicies;

class ServerPolicy
{
    use HasRolePolicies;

    public function viewAny(User $user, Project $project): bool
    {
        return $this->hasReadAccess($user, $project);
    }

    public function view(User $user, Server $server): bool
    {
        return $this->hasReadAccess($user, $server->project);
    }

    public function create(User $user, Project $project): bool
    {
        return $this->hasWriteAccess($user, $project);
    }

    public function update(User $user, Server $server): bool
    {
        return $this->hasWriteAccess($user, $server->project);
    }

    public function delete(User $user, Server $server): bool
    {
        return $this->hasOwnerAccess($user, $server->project);
    }

    public function manage(User $user, Server $server): bool
    {
        return $this->hasWriteAccess($user, $server->project) && $server->isReady();
    }
}
