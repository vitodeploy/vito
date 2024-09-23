<?php

namespace App\Policies;

use App\Enums\ServerStatus;
use App\Models\Site;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SitePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->currentProject?->users->contains($user);
    }

    public function view(User $user, Site $site): bool
    {
        return ($user->isAdmin() || $user->currentProject?->users->contains($user)) &&
            $site->server->status === ServerStatus::READY;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->currentProject?->users->contains($user);
    }

    public function update(User $user, Site $site): bool
    {
        return ($user->isAdmin() || $user->currentProject?->users->contains($user)) &&
            $site->server->status === ServerStatus::READY;
    }

    public function delete(User $user, Site $site): bool
    {
        return ($user->isAdmin() || $user->currentProject?->users->contains($user)) &&
            $site->server->status === ServerStatus::READY;
    }
}
