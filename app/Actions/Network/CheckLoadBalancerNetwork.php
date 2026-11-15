<?php

namespace App\Actions\Network;

use App\Models\ComponentBackend;
use App\Models\NetworkServer;
use App\Models\ServerIpAddress;
use Illuminate\Validation\ValidationException;

class CheckLoadBalancerNetwork
{
    public function validate(NetworkServer $member): void
    {
        $servers = $member->network->servers()->pluck('server_id');
        $backends = ComponentBackend::query()->whereNotNull('target_component_id')
            ->where(fn ($query) => $query->whereHas('component', fn ($query) => $query->where('server_id', $member->server_id))
                ->orWhereHas('targetComponent', fn ($query) => $query->where('server_id', $member->server_id)))
            ->with('component', 'targetComponent')->get();
        foreach ($backends as $backend) {
            if ($backend->component->server_id !== $backend->targetComponent->server_id
                && $servers->contains($backend->component->server_id) && $servers->contains($backend->targetComponent->server_id)) {
                throw ValidationException::withMessages(['network' => 'Remove the load balancer backends before removing their shared network memberships.']);
            }
        }
    }

    public function address(ServerIpAddress $address): void
    {
        foreach (NetworkServer::query()->where('server_ip_address_id', $address->id)->get() as $member) {
            $this->validate($member);
        }
    }
}
