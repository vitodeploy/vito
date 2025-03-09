<?php

namespace App\Policies;

use App\Models\ServerProvider;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ServerProviderPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ServerProvider $serverProvider): bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        if ($user->id === $serverProvider->user_id) {
            return true;
        }
        if ($serverProvider->project_id === null) {
            return true;
        }

        return (bool) $serverProvider->project?->users()->where('user_id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, ServerProvider $serverProvider): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->id === $serverProvider->user_id;
    }

    public function delete(User $user, ServerProvider $serverProvider): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->id === $serverProvider->user_id;
    }
}
