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
        return true;
    }

    public function view(User $user, PersonalAccessToken $personalAccessToken): bool
    {
        return $user->id === $personalAccessToken->tokenable_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, PersonalAccessToken $personalAccessToken): bool
    {
        return $user->id === $personalAccessToken->tokenable_id;
    }

    public function delete(User $user, PersonalAccessToken $personalAccessToken): bool
    {
        return $user->id === $personalAccessToken->tokenable_id;
    }
}
