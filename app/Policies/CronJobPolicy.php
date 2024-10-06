<?php

namespace App\Policies;

use App\Models\CronJob;
use App\Models\Server;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CronJobPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user, Server $server): bool
    {
        return ($user->isAdmin() || $server->project->users->contains($user)) && $server->isReady();
    }

    public function view(User $user, CronJob $cronjob): bool
    {
        return ($user->isAdmin() || $cronjob->server->project->users->contains($user)) &&
            $cronjob->server->isReady();
    }

    public function create(User $user, Server $server): bool
    {
        return ($user->isAdmin() || $server->project->users->contains($user)) && $server->isReady();
    }

    public function update(User $user, CronJob $cronjob): bool
    {
        return ($user->isAdmin() || $cronjob->server->project->users->contains($user)) &&
            $cronjob->server->isReady();
    }

    public function delete(User $user, CronJob $cronjob): bool
    {
        return ($user->isAdmin() || $cronjob->server->project->users->contains($user)) &&
            $cronjob->server->isReady();
    }
}
