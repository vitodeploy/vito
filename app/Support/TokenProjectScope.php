<?php

namespace App\Support;

use App\Models\PersonalAccessToken;
use App\Models\User;

/**
 * Token project-scope checks shared by user-level provider endpoints.
 */
class TokenProjectScope
{
    /**
     * Whether the current access token restricts the user to specific projects.
     */
    public static function restricted(?User $user): bool
    {
        $token = $user?->currentAccessToken();

        return $token instanceof PersonalAccessToken
            && $token->exists
            && $token->isProjectScoped();
    }

    /**
     * Whether a token may access the resource's project.
     */
    public static function allows(?User $user, ?int $projectId, bool $write = false): bool
    {
        $token = $user?->currentAccessToken();

        if (! $token instanceof PersonalAccessToken || ! $token->exists) {
            return true;
        }

        return $token->allowsProject($projectId, $write);
    }

    /**
     * Project IDs a restricted token may access, including global resources.
     *
     * @return list<int>
     */
    public static function allowedProjectIds(?User $user): array
    {
        $token = $user?->currentAccessToken();

        if (! $token instanceof PersonalAccessToken || ! $token->exists) {
            return [];
        }

        return $token->getProjectIds();
    }

    /**
     * Whether the token may create a provider for the target project.
     */
    public static function canCreate(?User $user, ?int $projectId, bool $global): bool
    {
        if (! self::restricted($user)) {
            return true;
        }

        if ($global || $projectId === null) {
            return false;
        }

        return self::allows($user, $projectId, write: true);
    }
}
