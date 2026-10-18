<?php

namespace App\Policies;

use App\Models\Application;
use App\Models\Component;
use App\Models\Environment;
use App\Models\Server;
use App\Models\Site;
use App\Models\User;
use App\Models\Worker;
use App\Traits\HasRolePolicies;
use Illuminate\Auth\Access\HandlesAuthorization;

class WorkerPolicy
{
    use HandlesAuthorization;
    use HasRolePolicies;

    public function viewAny(User $user, Server $server, ?Site $site = null, ?Component $component = null, ?Environment $environment = null, ?Application $application = null): bool
    {
        return $this->hasReadAccess($user, $server->project) &&
            $server->isReady() &&
            (! $site || $site->server_id === $server->id) &&
            (! $component || ($component->server_id === $server->id && $component->project()->id === $server->project_id &&
                $user->can('view', [$component, $environment ?? $component->environment, $application ?? $component->environment->application]))) &&
            $server->processManager();
    }

    public function view(User $user, Worker $worker, Server $server, ?Site $site = null, ?Component $component = null, ?Environment $environment = null, ?Application $application = null): bool
    {
        return $worker->server_id === $server->id &&
            (! $site || $worker->site_id === $site->id) &&
            (! $component || $worker->component_id === $component->id) &&
            $this->viewAny($user, $server, $site, $component ?? $worker->component, $environment, $application);
    }

    public function create(User $user, Server $server, ?Site $site = null, ?Component $component = null, ?Environment $environment = null, ?Application $application = null): bool
    {
        return $this->hasWriteAccess($user, $server->project) &&
            $this->viewAny($user, $server, $site, $component, $environment, $application);
    }

    public function manage(User $user, Server $server, ?Site $site = null): bool
    {
        return $this->create($user, $server, $site);
    }

    public function update(User $user, Worker $worker, Server $server, ?Site $site = null, ?Component $component = null, ?Environment $environment = null, ?Application $application = null): bool
    {
        return $worker->component_capability_id === null &&
            $this->hasWriteAccess($user, $server->project) &&
            $this->view($user, $worker, $server, $site, $component, $environment, $application);
    }

    public function delete(User $user, Worker $worker, Server $server, ?Site $site = null, ?Component $component = null, ?Environment $environment = null, ?Application $application = null): bool
    {
        return ! $worker->isSiteBootstrap() &&
            $this->update($user, $worker, $server, $site, $component, $environment, $application);
    }
}
