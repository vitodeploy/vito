<?php

namespace App\Traits;

use App\Models\User;

trait ChecksTokenProjectScope
{
    public function assignToProject(User $user, ?int $projectId): bool
    {
        return $user->tokenAllowsProject($projectId, write: true);
    }
}
