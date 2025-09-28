<?php

namespace App\Policies;

use App\Models\Server;
use App\Models\Site;
use App\Models\User;
use App\Traits\HasRolePolicies;
use Illuminate\Auth\Access\HandlesAuthorization;

class SitePolicy
{
    use HandlesAuthorization;
    use HasRolePolicies;

    public function viewAny(User $user, Server $server): bool
    {
        return $this->hasReadAccess($user, $server->project)
            && $server->isReady()
            && $server->webserver();
    }

    public function view(User $user, Site $site, Server $server): bool
    {
        $siteServer = $site->server;

        return $this->hasReadAccess($user, $siteServer->project)
            && $site->server_id === $server->id
            && $siteServer->isReady()
            && $siteServer->webserver();
    }

    public function create(User $user, Server $server): bool
    {
        return $this->hasWriteAccess($user, $server->project)
            && $server->isReady()
            && $server->webserver();
    }

    public function update(User $user, Site $site, Server $server): bool
    {
        $siteServer = $site->server;

        return $this->hasWriteAccess($user, $siteServer->project)
            && $site->server_id === $server->id
            && $siteServer->isReady()
            && $siteServer->webserver();
    }

    public function delete(User $user, Site $site, Server $server): bool
    {
        $siteServer = $site->server;

        return $this->hasWriteAccess($user, $siteServer->project)
            && $site->server_id === $server->id
            && $siteServer->isReady()
            && $siteServer->webserver();
    }
}
