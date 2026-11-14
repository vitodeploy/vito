<?php

namespace App\Policies;

use App\Models\PersonalAccessToken;
use App\Models\ServerProvider;
use App\Models\User;
use App\Traits\ChecksTokenProjectScope;
use Illuminate\Auth\Access\HandlesAuthorization;
use Laravel\Sanctum\TransientToken;

class ServerProviderPolicy
{
    use ChecksTokenProjectScope;
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ServerProvider $serverProvider): bool
    {
        return $user->id === $serverProvider->user_id
            && $user->tokenAllowsProject($serverProvider->project_id);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, ServerProvider $serverProvider): bool
    {
        return $user->id === $serverProvider->user_id
            && $user->tokenAllowsProject($serverProvider->project_id, write: true);
    }

    /**
     * Non-secret credential values are only for callers who can already
     * rewrite them. API tokens must additionally carry the write ability.
     */
    public function revealCredentials(User $user, ServerProvider $serverProvider): bool
    {
        /** @var PersonalAccessToken|TransientToken|null $token */
        $token = $user->currentAccessToken();

        if ($token !== null && ! $token->can('write')) {
            return false;
        }

        return $this->update($user, $serverProvider);
    }

    public function delete(User $user, ServerProvider $serverProvider): bool
    {
        return $user->id === $serverProvider->user_id
            && $user->tokenAllowsProject($serverProvider->project_id, write: true);
    }
}
