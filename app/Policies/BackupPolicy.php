<?php

namespace App\Policies;

use App\Models\Backup;
use App\Models\Server;
use App\Models\User;
use App\Traits\HasRolePolicies;
use Illuminate\Auth\Access\HandlesAuthorization;

class BackupPolicy
{
    use HandlesAuthorization;
    use HasRolePolicies;

    public function viewAny(User $user, Server $server): bool
    {
        return $this->hasReadAccess($user, $server->project)
            && $server->isReady();
    }

    public function view(User $user, Backup $backup): bool
    {
        $server = $backup->server;

        return $this->hasReadAccess($user, $server->project)
            && $server->isReady();
    }

    public function create(User $user, Server $server): bool
    {
        return $this->hasWriteAccess($user, $server->project)
            && $server->isReady();
    }

    public function update(User $user, Backup $backup): bool
    {
        $server = $backup->server;

        return $this->hasWriteAccess($user, $server->project)
            && $server->isReady();
    }

    public function delete(User $user, Backup $backup): bool
    {
        $server = $backup->server;

        return $this->hasWriteAccess($user, $server->project)
            && $server->isReady();
    }
}
