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
        return true;
    }

    public function view(User $user, NotificationChannel $notificationChannel): bool
    {
        return $user->id === $notificationChannel->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, NotificationChannel $notificationChannel): bool
    {
        return $user->id === $notificationChannel->user_id;
    }

    public function delete(User $user, NotificationChannel $notificationChannel): bool
    {
        return $user->id === $notificationChannel->user_id;
    }
}
