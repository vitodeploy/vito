<?php

namespace App\Policies;

use App\Models\Script;
use App\Models\Server;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ScriptPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Script $script): bool
    {
        return $user->id === $script->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Script $script): bool
    {
        return $user->id === $script->user_id;
    }

    public function execute(User $user, Script $script, Server $server): bool
    {
        return $user->id === $script->user_id && $server->project->users->contains($user);
    }

    public function delete(User $user, Script $script): bool
    {
        return $user->id === $script->user_id;
    }
}
