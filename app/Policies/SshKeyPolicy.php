<?php

namespace App\Policies;

use App\Models\SshKey;
use App\Models\User;

class SshKeyPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, SshKey $sshKey): bool
    {
        return $user->id === $sshKey->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, SshKey $sshKey): bool
    {
        return $user->id === $sshKey->user_id;
    }

    public function delete(User $user, SshKey $sshKey): bool
    {
        return $user->id === $sshKey->user_id;
    }
}
