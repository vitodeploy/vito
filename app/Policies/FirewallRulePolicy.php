<?php

namespace App\Policies;

use App\Models\FirewallRule;
use App\Models\Server;
use App\Models\User;
use App\Traits\HasRolePolicies;
use Illuminate\Auth\Access\HandlesAuthorization;

class FirewallRulePolicy
{
    use HandlesAuthorization;
    use HasRolePolicies;

    public function viewAny(User $user, Server $server): bool
    {
        return $this->hasReadAccess($user, $server->project)
            && $server->isReady()
            && $server->firewall();
    }

    public function view(User $user, FirewallRule $rule): bool
    {
        $server = $rule->server;

        return $this->hasReadAccess($user, $server->project) &&
            $server->isReady();
    }

    public function create(User $user, Server $server): bool
    {
        return $this->hasWriteAccess($user, $server->project)
            && $server->isReady()
            && $server->firewall();
    }

    public function update(User $user, FirewallRule $rule): bool
    {
        $server = $rule->server;

        return $this->hasWriteAccess($user, $server->project) &&
            $server->isReady();
    }

    public function delete(User $user, FirewallRule $rule): bool
    {
        $server = $rule->server;

        return $this->hasWriteAccess($user, $server->project) &&
            $server->isReady();
    }
}
