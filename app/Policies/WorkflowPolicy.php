<?php

namespace App\Policies;

use App\Models\PersonalAccessToken;
use App\Models\Project;
use App\Models\User;
use App\Models\Workflow;
use App\Traits\HasRolePolicies;
use Illuminate\Auth\Access\HandlesAuthorization;
use Laravel\Sanctum\TransientToken;

class WorkflowPolicy
{
    use HandlesAuthorization;
    use HasRolePolicies;

    public function viewAny(User $user, Project $project): bool
    {
        return $this->hasReadAccess($user, $project);
    }

    public function view(User $user, Workflow $workflow): bool
    {
        return $this->hasReadAccess($user, $workflow->project);
    }

    public function create(User $user, Project $project): bool
    {
        return $this->hasWriteAccess($user, $project);
    }

    public function update(User $user, Workflow $workflow): bool
    {
        return $this->hasWriteAccess($user, $workflow->project);
    }

    /**
     * Action inputs can hold credentials, so only editors on a session or a
     * write token see their saved values.
     */
    public function revealInputs(User $user, Workflow $workflow): bool
    {
        /** @var PersonalAccessToken|TransientToken|null $token */
        $token = $user->currentAccessToken();

        if ($token !== null && ! $token->can('write')) {
            return false;
        }

        return $this->update($user, $workflow);
    }

    public function delete(User $user, Workflow $workflow): bool
    {
        return $this->hasWriteAccess($user, $workflow->project);
    }
}
