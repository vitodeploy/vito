<?php

namespace App\Policies;

use App\Models\Server;
use App\Models\Site;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SitePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user, Server $server): bool
    {
        return ($user->isAdmin() || $server->project->users->contains($user))
            && $server->isReady()
            && $server->webserver();
    }

    public function view(User $user, Site $site, Server $server): bool
    {
        return ($user->isAdmin() || $site->server->project->users->contains($user))
            && $site->server_id === $server->id
            && $site->server->isReady()
            && $site->server->webserver();
    }

    public function create(User $user, Server $server): bool
    {
        return ($user->isAdmin() || $server->project->users->contains($user))
            && $server->isReady()
            && $server->webserver();
    }

    public function update(User $user, Site $site, Server $server): bool
    {
        return ($user->isAdmin() || $site->server->project->users->contains($user))
            && $site->server_id === $server->id
            && $site->server->isReady()
            && $site->server->webserver();
    }

    public function delete(User $user, Site $site, Server $server): bool
    {
        return ($user->isAdmin() || $site->server->project->users->contains($user))
            && $site->server_id === $server->id
            && $site->server->isReady()
            && $site->server->webserver();
    }
}
