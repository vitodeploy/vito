<?php

namespace App\Policies;

use App\Models\ServerProvider;
use App\Models\User;
use App\Traits\ChecksTokenProjectScope;
use Illuminate\Auth\Access\HandlesAuthorization;

class ServerProviderPolicy
{
    use ChecksTokenProjectScope;
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ServerProvider $serverProvider): bool
    {
        return $user->id === $serverProvider->user_id
            && $user->tokenAllowsProject($serverProvider->project_id);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, ServerProvider $serverProvider): bool
    {
        return $user->id === $serverProvider->user_id
            && $user->tokenAllowsProject($serverProvider->project_id, write: true);
    }

    public function delete(User $user, ServerProvider $serverProvider): bool
    {
        return $user->id === $serverProvider->user_id
            && $user->tokenAllowsProject($serverProvider->project_id, write: true);
    }
}
