<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workflow;
use Illuminate\Auth\Access\HandlesAuthorization;

class WorkflowPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Workflow $workflow): bool
    {
        return $user->id === $workflow->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Workflow $workflow): bool
    {
        return $user->id === $workflow->user_id;
    }

    public function delete(User $user, Workflow $workflow): bool
    {
        return $user->id === $workflow->user_id;
    }
}
