<?php

namespace App\Policies;

use App\Models\CronJob;
use App\Models\Server;
use App\Models\Site;
use App\Models\User;
use App\Traits\HasRolePolicies;
use Illuminate\Auth\Access\HandlesAuthorization;

class CronJobPolicy
{
    use HandlesAuthorization;
    use HasRolePolicies;

    public function viewAny(User $user, Server $server, ?Site $site = null): bool
    {
        return $this->hasReadAccess($user, $server->project) && $server->isReady();
    }

    public function view(User $user, CronJob $cronjob, Server $server, ?Site $site = null): bool
    {
        $cronJobServer = $cronjob->server;

        return $this->hasReadAccess($user, $cronJobServer->project) &&
            $cronJobServer->isReady() &&
            $cronjob->server_id === $server->id;
    }

    public function create(User $user, Server $server, ?Site $site = null): bool
    {
        return $this->hasWriteAccess($user, $server->project) && $server->isReady();
    }

    public function update(User $user, CronJob $cronjob, Server $server, ?Site $site = null): bool
    {
        $cronJobServer = $cronjob->server;

        return $this->hasWriteAccess($user, $cronJobServer->project) &&
            $cronJobServer->isReady() &&
            $cronjob->server_id === $server->id;
    }

    public function delete(User $user, CronJob $cronjob, Server $server, ?Site $site = null): bool
    {
        $cronJobServer = $cronjob->server;

        return $this->hasWriteAccess($user, $cronJobServer->project) &&
            $cronJobServer->isReady() &&
            $cronjob->server_id === $server->id;
    }
}
