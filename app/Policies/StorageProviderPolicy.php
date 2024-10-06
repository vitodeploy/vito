<?php

namespace App\Policies;

use App\Models\StorageProvider;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class StorageProviderPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, StorageProvider $storageProvider): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, StorageProvider $storageProvider): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, StorageProvider $storageProvider): bool
    {
        return $user->isAdmin();
    }
}
