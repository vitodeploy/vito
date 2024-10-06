<?php

namespace App\Policies;

use App\Enums\ServiceStatus;
use App\Models\Server;
use App\Models\Service;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ServicePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user, Server $server): bool
    {
        return ($user->isAdmin() || $server->project->users->contains($user)) && $server->isReady();
    }

    public function view(User $user, Service $service): bool
    {
        return ($user->isAdmin() || $service->server->project->users->contains($user)) && $service->server->isReady();
    }

    public function create(User $user, Server $server): bool
    {
        return ($user->isAdmin() || $server->project->users->contains($user)) && $server->isReady();
    }

    public function update(User $user, Service $service): bool
    {
        return ($user->isAdmin() || $service->server->project->users->contains($user)) && $service->server->isReady();
    }

    public function delete(User $user, Service $service): bool
    {
        return ($user->isAdmin() || $service->server->project->users->contains($user)) && $service->server->isReady();
    }

    public function start(User $user, Service $service): bool
    {
        return ($user->isAdmin() || $service->server->project->users->contains($user)) &&
            $service->server->isReady() &&
            in_array($service->status, [
                ServiceStatus::STOPPED,
                ServiceStatus::FAILED,
            ]);
    }

    public function stop(User $user, Service $service): bool
    {
        return ($user->isAdmin() || $service->server->project->users->contains($user)) &&
            $service->server->isReady() &&
            in_array($service->status, [
                ServiceStatus::READY,
                ServiceStatus::FAILED,
            ]);
    }

    public function restart(User $user, Service $service): bool
    {
        return ($user->isAdmin() || $service->server->project->users->contains($user)) &&
            $service->server->isReady() &&
            in_array($service->status, [
                ServiceStatus::READY,
                ServiceStatus::FAILED,
                ServiceStatus::STOPPED,
            ]);
    }

    public function enable(User $user, Service $service): bool
    {
        return ($user->isAdmin() || $service->server->project->users->contains($user)) &&
            $service->server->isReady() &&
            $service->status == ServiceStatus::DISABLED;
    }

    public function disable(User $user, Service $service): bool
    {
        return ($user->isAdmin() || $service->server->project->users->contains($user)) &&
            $service->server->isReady() &&
              in_array($service->status, [
                  ServiceStatus::READY,
                  ServiceStatus::STOPPED,
              ]);
    }
}
