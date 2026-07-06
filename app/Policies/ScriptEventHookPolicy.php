<?php

namespace App\Policies;

use App\Models\Script;
use App\Models\ScriptEventHook;
use App\Models\User;
use App\Traits\HasRolePolicies;

class ScriptEventHookPolicy
{
    use HasRolePolicies;

    public function viewAny(User $user, Script $script): bool
    {
        return $user->id === $script->user_id;
    }

    public function create(User $user, Script $script): bool
    {
        return $user->id === $script->user_id;
    }

    public function update(User $user, ScriptEventHook $hook): bool
    {
        return $user->id === $hook->user_id;
    }

    public function delete(User $user, ScriptEventHook $hook): bool
    {
        return $user->id === $hook->user_id;
    }
}
