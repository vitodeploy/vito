<?php

namespace App\Policies;

use App\Models\Queue;
use App\Models\Server;
use App\Models\Site;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class QueuePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user, Site $site, Server $server): bool
    {
        return ($user->isAdmin() || $server->project->users->contains($user)) &&
            $server->isReady() &&
            $site->isReady();
    }

    public function view(User $user, Queue $queue, Site $site, Server $server): bool
    {
        return ($user->isAdmin() || $server->project->users->contains($user)) &&
            $site->server_id === $server->id &&
            $server->isReady() &&
            $site->isReady() &&
            $queue->site_id === $site->id;
    }

    public function create(User $user, Site $site, Server $server): bool
    {
        return ($user->isAdmin() || $server->project->users->contains($user)) &&
            $server->isReady() &&
            $site->isReady();
    }

    public function update(User $user, Queue $queue, Site $site, Server $server): bool
    {
        return ($user->isAdmin() || $server->project->users->contains($user)) &&
           $site->server_id === $server->id &&
           $server->isReady() &&
           $site->isReady() &&
           $queue->site_id === $site->id;
    }

    public function delete(User $user, Queue $queue, Site $site, Server $server): bool
    {
        return ($user->isAdmin() || $server->project->users->contains($user)) &&
            $site->server_id === $server->id &&
            $server->isReady() &&
            $site->isReady() &&
            $queue->site_id === $site->id;
    }
}
