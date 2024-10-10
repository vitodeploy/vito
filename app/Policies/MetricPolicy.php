<?php

namespace App\Policies;

use App\Models\Metric;
use App\Models\Server;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MetricPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user, Server $server): bool
    {
        return ($user->isAdmin() || $server->project->users->contains($user)) &&
            $server->service('monitoring') &&
            $server->isReady();
    }

    public function view(User $user, Metric $metric): bool
    {

        return ($user->isAdmin() || $metric->server->project->users->contains($user)) &&
            $metric->server->service('monitoring') &&
            $metric->server->isReady();
    }

    public function create(User $user, Server $server): bool
    {
        return ($user->isAdmin() || $server->project->users->contains($user)) &&
            $server->service('monitoring') &&
            $server->isReady();
    }

    public function update(User $user, Metric $metric): bool
    {
        return ($user->isAdmin() || $metric->server->project->users->contains($user)) &&
            $metric->server->service('monitoring') &&
            $metric->server->isReady();
    }

    public function delete(User $user, Metric $metric): bool
    {
        return ($user->isAdmin() || $metric->server->project->users->contains($user)) &&
            $metric->server->service('monitoring') &&
            $metric->server->isReady();
    }
}
