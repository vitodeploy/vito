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
        return $user->isAdmin();
    }

    public function view(User $user, ServerProvider $serverProvider): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, ServerProvider $serverProvider): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, ServerProvider $serverProvider): bool
    {
        return $user->isAdmin();
    }
}
