<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use App\Models\Workflow;
use App\Traits\HasRolePolicies;
use Illuminate\Auth\Access\HandlesAuthorization;

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

    public function delete(User $user, Workflow $workflow): bool
    {
        return $this->hasWriteAccess($user, $workflow->project);
    }
}
