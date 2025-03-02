<?php

namespace App\Policies;

use App\Enums\SiteFeature;
use App\Models\Command;
use App\Models\Server;
use App\Models\Site;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CommandPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user, Site $site, Server $server): bool
    {
        return ($user->isAdmin() || $server->project->users->contains($user)) &&
            $server->isReady() &&
            $site->hasFeature(SiteFeature::COMMANDS) &&
            $site->isReady();
    }

    public function view(User $user, Command $command, Site $site, Server $server): bool
    {
        return ($user->isAdmin() || $server->project->users->contains($user)) &&
            $site->server_id === $server->id &&
            $server->isReady() &&
            $site->isReady() &&
            $site->hasFeature(SiteFeature::COMMANDS) &&
            $command->site_id === $site->id;
    }

    public function create(User $user, Site $site, Server $server): bool
    {
        return ($user->isAdmin() || $server->project->users->contains($user)) &&
            $server->isReady() &&
            $site->hasFeature(SiteFeature::COMMANDS) &&
            $site->isReady();
    }

    public function update(User $user, Command $command, Site $site, Server $server): bool
    {
        return ($user->isAdmin() || $server->project->users->contains($user)) &&
           $site->server_id === $server->id &&
           $server->isReady() &&
           $site->isReady() &&
           $site->hasFeature(SiteFeature::COMMANDS) &&
           $command->site_id === $site->id;
    }

    public function delete(User $user, Command $command, Site $site, Server $server): bool
    {
        return ($user->isAdmin() || $server->project->users->contains($user)) &&
            $site->server_id === $server->id &&
            $server->isReady() &&
            $site->isReady() &&
            $site->hasFeature(SiteFeature::COMMANDS) &&
            $command->site_id === $site->id;
    }
}
