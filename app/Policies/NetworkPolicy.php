<?php

namespace App\Policies;

use App\Actions\Network\CheckNetworkStranding;
use App\Enums\NetworkType;
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

    /**
     * Provider-managed networks mirror the cloud provider and are removed by sync when the
     * VPC disappears. One that sync can never reach again — its connection deleted, or no
     * member still identifiable at the provider — stays deletable to avoid stranding the row.
     */
    public function delete(User $user, Network $network): bool
    {
        if ($network->type === NetworkType::PROVIDER
            && $network->server_provider_id !== null
            && ! app(CheckNetworkStranding::class)->handle($network)) {
            return false;
        }

        return $this->hasWriteAccess($user, $network->project);
    }
}
