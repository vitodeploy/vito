<?php

namespace App\Policies;

use App\Models\Backup;
use App\Models\Server;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BackupPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user, Server $server): bool
    {
        return ($user->isAdmin() || $server->project->users->contains($user)) && $server->isReady();
    }

    public function view(User $user, Backup $backup): bool
    {
        return ($user->isAdmin() || $backup->server->project->users->contains($user)) &&
            $backup->server->isReady();
    }

    public function create(User $user, Server $server): bool
    {
        return ($user->isAdmin() || $server->project->users->contains($user)) && $server->isReady();
    }

    public function update(User $user, Backup $backup): bool
    {
        return ($user->isAdmin() || $backup->server->project->users->contains($user)) &&
            $backup->server->isReady();
    }

    public function delete(User $user, Backup $backup): bool
    {
        return ($user->isAdmin() || $backup->server->project->users->contains($user)) &&
            $backup->server->isReady();
    }
}
