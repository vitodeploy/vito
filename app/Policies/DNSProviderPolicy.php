<?php

namespace App\Policies;

use App\Models\DNSProvider;
use App\Models\PersonalAccessToken;
use App\Models\User;
use App\Traits\ChecksTokenProjectScope;
use Laravel\Sanctum\TransientToken;

class DNSProviderPolicy
{
    use ChecksTokenProjectScope;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, DNSProvider $dnsProvider): bool
    {
        return $user->id === $dnsProvider->user_id
            && $user->tokenAllowsProject($dnsProvider->project_id);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, DNSProvider $dnsProvider): bool
    {
        return $user->id === $dnsProvider->user_id
            && $user->tokenAllowsProject($dnsProvider->project_id, write: true);
    }

    /**
     * Non-secret credential values are only for callers who can already
     * rewrite them. API tokens must additionally carry the write ability.
     */
    public function revealCredentials(User $user, DNSProvider $dnsProvider): bool
    {
        /** @var PersonalAccessToken|TransientToken|null $token */
        $token = $user->currentAccessToken();

        if ($token !== null && ! $token->can('write')) {
            return false;
        }

        return $this->update($user, $dnsProvider);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, DNSProvider $dnsProvider): bool
    {
        return $user->id === $dnsProvider->user_id
            && $user->tokenAllowsProject($dnsProvider->project_id, write: true);
    }
}
