<?php

namespace App\Policies;

use App\Enums\SiteFeature;
use App\Models\Server;
use App\Models\Site;
use App\Models\User;
use App\Models\Worker;
use Illuminate\Auth\Access\HandlesAuthorization;

class WorkerPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user, Server $server, ?Site $site = null): bool
    {
        return ($user->isAdmin() || $server->project->users->contains($user)) &&
            $server->isReady() &&
            (
                ! $site instanceof \App\Models\Site ||
                (
                    $site->hasFeature(SiteFeature::WORKERS) &&
                    $site->isReady()
                )
            );
    }

    public function view(User $user, Worker $worker, Server $server, ?Site $site = null): bool
    {
        return ($user->isAdmin() || $server->project->users->contains($user)) &&
            $site->server_id === $server->id &&
            $server->isReady() &&
            (
                ! $site instanceof \App\Models\Site ||
                (
                    $site->hasFeature(SiteFeature::WORKERS) &&
                    $site->isReady() &&
                    $worker->site_id === $site->id
                )
            );
    }

    public function create(User $user, Server $server, ?Site $site = null): bool
    {
        return ($user->isAdmin() || $server->project->users->contains($user)) &&
            $server->isReady() &&
            (
                ! $site instanceof \App\Models\Site ||
                (
                    $site->hasFeature(SiteFeature::WORKERS) &&
                    $site->isReady()
                )
            );
    }

    public function update(User $user, Worker $worker, Server $server, ?Site $site = null): bool
    {
        return ($user->isAdmin() || $server->project->users->contains($user)) &&
            $site->server_id === $server->id &&
            $server->isReady() &&
            (
                ! $site instanceof \App\Models\Site ||
                (
                    $site->hasFeature(SiteFeature::WORKERS) &&
                    $site->isReady() &&
                    $worker->site_id === $site->id
                )
            );
    }

    public function delete(User $user, Worker $worker, Server $server, ?Site $site = null): bool
    {
        return ($user->isAdmin() || $server->project->users->contains($user)) &&
            $site->server_id === $server->id &&
            $server->isReady() &&
            (
                ! $site instanceof \App\Models\Site ||
                (
                    $site->hasFeature(SiteFeature::WORKERS) &&
                    $site->isReady() &&
                    $worker->site_id === $site->id
                )
            );
    }
}
