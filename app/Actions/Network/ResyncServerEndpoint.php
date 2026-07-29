<?php

namespace App\Actions\Network;

use App\Enums\NetworkServerStatus;
use App\Enums\NetworkType;
use App\Models\NetworkServer;
use App\Models\Server;

class ResyncServerEndpoint
{
    public function __construct(private DispatchNetworkServerSync $sync) {}

    /**
     * The server's public address feeds every peer's WireGuard endpoint and handshake firewall
     * rule, so a change to it has to be pushed to the other members of each of its WireGuard
     * networks — otherwise they keep dialling the old address.
     */
    public function handle(Server $server): void
    {
        NetworkServer::query()
            ->where('server_id', $server->id)
            ->where('status', '!=', NetworkServerStatus::LEAVING)
            ->whereHas('network', fn ($query) => $query->where('type', NetworkType::WIREGUARD))
            ->with('network')
            ->get()
            ->each(fn (NetworkServer $membership) => $this->sync->resyncMembers($membership->network, $membership->id));
    }
}
