<?php

namespace App\Actions\Network;

use App\Enums\ComponentType;
use App\Models\EnvironmentAttachment;
use App\Models\NetworkServer;
use App\Models\ServerIpAddress;
use Illuminate\Validation\ValidationException;

class CheckEnvironmentResourceNetwork
{
    public function validate(NetworkServer $member): void
    {
        $servers = $member->network->servers()->pluck('server_id');
        $attachments = EnvironmentAttachment::query()
            ->where(fn ($query) => $query->whereIn('server_id', $servers)
                ->orWhereHas('environment.components', fn ($query) => $query->where('server_id', $member->server_id)))
            ->with('environment.components')->get();
        foreach ($attachments as $attachment) {
            foreach ($attachment->environment->components as $component) {
                if ($component->type === ComponentType::APP && $component->server_id !== $attachment->server_id
                    && in_array($member->server_id, [$component->server_id, $attachment->server_id], true)
                    && $servers->contains($component->server_id) && $servers->contains($attachment->server_id)) {
                    throw ValidationException::withMessages(['network' => 'Detach environment resources and finish cleanup before removing their shared network memberships.']);
                }
            }
            foreach ($attachment->access ?? [] as $entry) {
                if ($entry['network_id'] === $member->network_id && in_array($member->server_id, [$entry['server_id'], $attachment->server_id], true)) {
                    throw ValidationException::withMessages(['network' => 'Wait for environment resource access cleanup before removing this network membership.']);
                }
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
