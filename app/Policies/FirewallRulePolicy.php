<?php

namespace App\Policies;

use App\Models\FirewallRule;
use App\Models\Server;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class FirewallRulePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user, Server $server): bool
    {
        return ($user->isAdmin() || $server->project->users->contains($user)) && $server->isReady();
    }

    public function view(User $user, FirewallRule $rule): bool
    {
        return ($user->isAdmin() || $rule->server->project->users->contains($user)) &&
            $rule->server->isReady();
    }

    public function create(User $user, Server $server): bool
    {
        return ($user->isAdmin() || $server->project->users->contains($user)) && $server->isReady();
    }

    public function update(User $user, FirewallRule $rule): bool
    {
        return ($user->isAdmin() || $rule->server->project->users->contains($user)) &&
            $rule->server->isReady();
    }

    public function delete(User $user, FirewallRule $rule): bool
    {
        return ($user->isAdmin() || $rule->server->project->users->contains($user)) &&
            $rule->server->isReady();
    }
}
