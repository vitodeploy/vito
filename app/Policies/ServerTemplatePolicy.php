<?php

namespace App\Policies;

use App\Models\ServerTemplate;
use App\Models\User;

class ServerTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, ServerTemplate $serverTemplate): bool
    {
        return $user->id === $serverTemplate->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, ServerTemplate $serverTemplate): bool
    {
        return $user->id === $serverTemplate->user_id;
    }

    public function delete(User $user, ServerTemplate $serverTemplate): bool
    {
        return $user->id === $serverTemplate->user_id;
    }
}
