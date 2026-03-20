<?php

namespace App\Policies;

use App\Models\HostedDomain;
use App\Models\Server;
use App\Models\Site;
use App\Models\User;
use App\Traits\HasRolePolicies;
use Illuminate\Auth\Access\HandlesAuthorization;

class HostedDomainPolicy
{
    use HandlesAuthorization;
    use HasRolePolicies;

    public function viewAny(User $user, Site $site, Server $server): bool
    {
        return $this->hasReadAccess($user, $server->project) &&
            $site->server_id === $server->id &&
            $server->isReady() &&
            $site->isReady();
    }

    public function create(User $user, Site $site, Server $server): bool
    {
        return $this->hasWriteAccess($user, $server->project) &&
            $site->server_id === $server->id &&
            $server->isReady() &&
            $site->isReady();
    }

    public function update(User $user, HostedDomain $hostedDomain, Site $site, Server $server): bool
    {
        return $this->hasWriteAccess($user, $server->project) &&
            $site->server_id === $server->id &&
            $server->isReady() &&
            $site->isReady() &&
            $hostedDomain->site_id === $site->id;
    }

    public function delete(User $user, HostedDomain $hostedDomain, Site $site, Server $server): bool
    {
        return $this->hasWriteAccess($user, $server->project) &&
            $site->server_id === $server->id &&
            $server->isReady() &&
            $site->isReady() &&
            $hostedDomain->site_id === $site->id;
    }
}
