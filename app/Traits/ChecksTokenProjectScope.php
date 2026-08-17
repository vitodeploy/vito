<?php

namespace App\Traits;

use App\Models\User;

trait ChecksTokenProjectScope
{
    /**
     * Determine whether the user can place the resource in the given project.
     * A null project id means the resource would become global, which a
     * project-scoped API token may never write.
     */
    public function assignToProject(User $user, ?int $projectId): bool
    {
        return $user->tokenAllowsProject($projectId, write: true);
    }
}
