<?php

namespace App\Actions\Network;

use App\Enums\NetworkType;
use App\Models\Network;
use App\Models\NetworkServer;
use App\ServerProviders\ProvidesPrivateNetworks;

class CheckNetworkStranding
{
    /**
     * Whether a provider network can no longer be asked about at the provider: its connection
     * cannot discover private networks, or none of its members still carries the instance id the
     * provider identifies servers by. Sync can neither reconcile nor prune such a network, so the
     * policy lets it be deleted by hand instead of stranding the row.
     *
     * A network with no members at all is not stranded — sync reaps that one on its own.
     */
    public function handle(Network $network): bool
    {
        if ($network->type !== NetworkType::PROVIDER || $network->server_provider_id === null) {
            return false;
        }

        $provider = $network->serverProvider?->provider();

        if (! $provider instanceof ProvidesPrivateNetworks) {
            return true;
        }

        $members = $network->servers()->with('server:id,provider_data')->get();

        if ($members->isEmpty()) {
            return false;
        }

        $key = $provider->instanceIdKey();

        return ! $members->contains(
            fn (NetworkServer $member): bool => ($member->server->provider_data[$key] ?? '') !== ''
        );
    }
}
