<?php

namespace App\Actions\Network;

use App\Enums\NetworkServerStatus;
use App\Enums\NetworkType;
use App\Models\NetworkServer;
use App\Models\Server;

class ResyncServerEndpoint
{
    public function __construct(private DispatchNetworkServerSync $sync) {}

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
