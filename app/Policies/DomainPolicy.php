<?php

namespace App\Policies;

use App\Models\Domain;
use App\Models\Project;
use App\Models\User;
use App\Traits\HasRolePolicies;
use Illuminate\Auth\Access\HandlesAuthorization;

class DomainPolicy
{
    use HandlesAuthorization;
    use HasRolePolicies;

    public function viewAny(User $user, Project $project): bool
    {
        return $this->hasReadAccess($user, $project);
    }

    public function view(User $user, Domain $domain): bool
    {
        return $this->hasReadAccess($user, $domain->project);
    }

    public function create(User $user, Project $project): bool
    {
        return $this->hasWriteAccess($user, $project);
    }

    public function update(User $user, Domain $domain): bool
    {
        return $this->hasWriteAccess($user, $domain->project);
    }

    public function delete(User $user, Domain $domain): bool
    {
        return $this->hasWriteAccess($user, $domain->project);
    }
}
