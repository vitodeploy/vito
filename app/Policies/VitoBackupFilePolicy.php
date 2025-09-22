<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VitoBackupFile;
use Illuminate\Auth\Access\HandlesAuthorization;

class VitoBackupFilePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, VitoBackupFile $vitoBackupFile): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, VitoBackupFile $vitoBackupFile): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, VitoBackupFile $vitoBackupFile): bool
    {
        return $user->isAdmin();
    }
}
