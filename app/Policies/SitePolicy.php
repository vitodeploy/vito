<?php

namespace App\Policies;

use App\Models\PersonalAccessToken;
use App\Models\Server;
use App\Models\Site;
use App\Models\User;
use App\Traits\HasRolePolicies;
use Illuminate\Auth\Access\HandlesAuthorization;
use Laravel\Sanctum\TransientToken;

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

    /**
     * Raw .env content and unmasked secret values are only for callers who can
     * already rewrite them. API tokens must additionally carry the write ability.
     */
    public function revealEnv(User $user, Site $site, Server $server): bool
    {
        /** @var PersonalAccessToken|TransientToken|null $token */
        $token = $user->currentAccessToken();

        if ($token !== null && ! $token->can('write')) {
            return false;
        }

        return $this->update($user, $site, $server);
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
