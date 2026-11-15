<?php

namespace App\Actions\Network;

use App\Enums\NetworkType;
use App\Models\Network;
use App\Models\NetworkServer;
use App\ServerProviders\ProvidesPrivateNetworks;

class CheckNetworkStranding
{
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
