<?php

namespace App\Policies;

use App\Models\Network;
use App\Models\Project;
use App\Models\User;
use App\Traits\HasRolePolicies;
use Illuminate\Auth\Access\HandlesAuthorization;

class NetworkPolicy
{
    use HandlesAuthorization;
    use HasRolePolicies;

    public function viewAny(User $user, Project $project): bool
    {
        return $this->hasReadAccess($user, $project);
    }

    public function view(User $user, Network $network): bool
    {
        return $this->hasReadAccess($user, $network->project);
    }

    public function create(User $user, Project $project): bool
    {
        return $this->hasWriteAccess($user, $project);
    }

    public function update(User $user, Network $network): bool
    {
        return $this->hasWriteAccess($user, $network->project);
    }

    public function delete(User $user, Network $network): bool
    {
        return $this->hasWriteAccess($user, $network->project);
    }
}
