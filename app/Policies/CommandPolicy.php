<?php

namespace App\Policies;

use App\Models\Command;
use App\Models\Server;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CommandPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Command $command): bool
    {
        return true;
    }

    public function create(User $user, Server $server): bool
    {
        return $user->isAdmin() || $server->project->users->contains($user);
    }

    public function update(User $user, Command $command, Server $server): bool
    {
        return $user->isAdmin() || $server->project->users->contains($user);
    }

    public function execute(User $user, Command $command, Server $server): bool
    {
        return $user->isAdmin() || $server->project->users->contains($user);
    }

    public function delete(User $user, Command $command, Server $server): bool
    {
        return $user->isAdmin() || $server->project->users->contains($user);
    }
}
