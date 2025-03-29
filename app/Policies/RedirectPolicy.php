<?php

namespace App\Policies;

use App\Models\Server;
use App\Models\Site;
use App\Models\User;

class RedirectPolicy
{
    public function view(User $user, Site $site, Server $server): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $site->server->project->users->contains($user);
    }

    public function create(User $user, Site $site, Server $server): bool
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
