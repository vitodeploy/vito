<?php

namespace App\Policies;

use App\Models\NotificationChannel;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class NotificationChannelPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, NotificationChannel $notificationChannel): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, NotificationChannel $notificationChannel): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, NotificationChannel $notificationChannel): bool
    {
        return $user->isAdmin();
    }
}
