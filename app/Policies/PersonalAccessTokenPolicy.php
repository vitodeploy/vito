<?php

namespace App\Policies;

use App\Models\PersonalAccessToken;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PersonalAccessTokenPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, PersonalAccessToken $personalAccessToken): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, PersonalAccessToken $personalAccessToken): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, PersonalAccessToken $personalAccessToken): bool
    {
        return $user->isAdmin();
    }

    public function deleteMany(User $user): bool
    {
        return $user->isAdmin();
    }
}
