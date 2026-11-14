<?php

namespace App\Policies;

use App\Models\Application;
use App\Models\Component;
use App\Models\CronJob;
use App\Models\Environment;
use App\Models\Server;
use App\Models\Site;
use App\Models\User;
use App\Traits\HasRolePolicies;
use Illuminate\Auth\Access\HandlesAuthorization;

class CronJobPolicy
{
    use HandlesAuthorization;
    use HasRolePolicies;

    public function viewAny(User $user, Server $server, ?Site $site = null, ?Component $component = null, ?Environment $environment = null, ?Application $application = null): bool
    {
        return $this->hasReadAccess($user, $server->project) && $server->isReady() &&
            (! $site || $site->server_id === $server->id) &&
            (! $component || ($component->server_id === $server->id && $component->project()->id === $server->project_id &&
                $user->can('view', [$component, $environment ?? $component->environment, $application ?? $component->environment->application])));
    }

    public function view(User $user, CronJob $cronjob, Server $server, ?Site $site = null, ?Component $component = null, ?Environment $environment = null, ?Application $application = null): bool
    {
        return $cronjob->server_id === $server->id &&
            (! $site || $cronjob->site_id === $site->id) &&
            (! $component || $cronjob->component_id === $component->id) &&
            $this->viewAny($user, $server, $site, $component ?? $cronjob->component, $environment, $application);
    }

    public function create(User $user, Server $server, ?Site $site = null, ?Component $component = null, ?Environment $environment = null, ?Application $application = null): bool
    {
        return $this->hasWriteAccess($user, $server->project) &&
            $this->viewAny($user, $server, $site, $component, $environment, $application);
    }

    public function update(User $user, CronJob $cronjob, Server $server, ?Site $site = null, ?Component $component = null, ?Environment $environment = null, ?Application $application = null): bool
    {
        return $cronjob->component_capability_id === null &&
            $this->hasWriteAccess($user, $server->project) &&
            $this->view($user, $cronjob, $server, $site, $component, $environment, $application);
    }

    public function delete(User $user, CronJob $cronjob, Server $server, ?Site $site = null, ?Component $component = null, ?Environment $environment = null, ?Application $application = null): bool
    {
        return $this->update($user, $cronjob, $server, $site, $component, $environment, $application);
    }
}
