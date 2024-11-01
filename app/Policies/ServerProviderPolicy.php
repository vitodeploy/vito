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
        return $user->isAdmin() ||
            $user->id === $serverProvider->user_id ||
            $serverProvider->project_id === null ||
            $serverProvider->project?->users()->where('user_id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, ServerProvider $serverProvider): bool
    {
        return $user->isAdmin() || $user->id === $serverProvider->user_id;
    }

    public function delete(User $user, ServerProvider $serverProvider): bool
    {
        return $user->isAdmin() || $user->id === $serverProvider->user_id;
    }
}
