<?php

namespace App\Policies;

use App\Models\Site;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SitePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Site $site): bool
    {
        return $user->isAdmin() || $site->server->project->users->contains($user);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->currentProject?->users->contains($user);
    }

    public function update(User $user, Site $site): bool
    {
        return $user->isAdmin() || $user->currentProject?->users->contains($user);
    }

    public function delete(User $user, Site $site): bool
    {
        return $user->isAdmin() || $user->currentProject?->users->contains($user);
    }
}
