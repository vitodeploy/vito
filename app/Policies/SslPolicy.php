<?php

namespace App\Policies;

use App\Models\Server;
use App\Models\Site;
use App\Models\Ssl;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SslPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user, Site $site, Server $server): bool
    {
        return ($user->isAdmin() || $server->project->users->contains($user)) &&
            $server->isReady() &&
            $site->isReady();
    }

    public function view(User $user, Ssl $ssl, Site $site, Server $server): bool
    {
        return ($user->isAdmin() || $server->project->users->contains($user)) &&
            $site->server_id === $server->id &&
            $server->isReady() &&
            $site->isReady() &&
            $ssl->site_id === $site->id;
    }

    public function create(User $user, Site $site, Server $server): bool
    {
        return ($user->isAdmin() || $server->project->users->contains($user)) &&
            $server->isReady() &&
            $site->isReady();
    }

    public function update(User $user, Ssl $ssl, Site $site, Server $server): bool
    {
        return ($user->isAdmin() || $server->project->users->contains($user)) &&
           $site->server_id === $server->id &&
           $server->isReady() &&
           $site->isReady() &&
           $ssl->site_id === $site->id;
    }

    public function delete(User $user, Ssl $ssl, Site $site, Server $server): bool
    {
        return ($user->isAdmin() || $server->project->users->contains($user)) &&
            $site->server_id === $server->id &&
            $server->isReady() &&
            $site->isReady() &&
            $ssl->site_id === $site->id;
    }
}
