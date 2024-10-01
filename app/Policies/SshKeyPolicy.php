<?php

namespace App\Policies;

use App\Models\Server;
use App\Models\SshKey;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SshKeyPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, SshKey $sshKey): bool
    {
        return $user->id === $sshKey->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, SshKey $sshKey): bool
    {
        return $user->id === $sshKey->user_id;
    }

    public function delete(User $user, SshKey $sshKey): bool
    {
        return $user->id === $sshKey->user_id;
    }

    public function viewAnyServer(User $user, Server $server): bool
    {
        return ($user->isAdmin() || $server->project->users->contains($user)) && $server->isReady();
    }

    public function viewServer(User $user, Server $server): bool
    {
        return ($user->isAdmin() || $server->project->users->contains($user)) &&
            $server->isReady();
    }

    public function createServer(User $user, Server $server): bool
    {
        return ($user->isAdmin() || $server->project->users->contains($user)) && $server->isReady();
    }

    public function updateServer(User $user, Server $server): bool
    {
        return ($user->isAdmin() || $server->project->users->contains($user)) &&
            $server->isReady();
    }

    public function deleteServer(User $user, Server $server): bool
    {
        return ($user->isAdmin() || $server->project->users->contains($user)) &&
            $server->isReady();
    }
}
