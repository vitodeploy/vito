<?php

namespace App\Actions\Network;

use App\Enums\NetworkType;
use App\Models\NetworkServer;

class RemoveServerFromNetwork
{
    public function __construct(
        private DispatchNetworkServerSync $sync,
        private RecomputeNetworkStatus $recompute,
        private ApplyNetworkFirewall $firewall,
    ) {}

    public function remove(NetworkServer $member): void
    {
        $network = $member->network;

        $this->sync->teardown($member);

        if ($network->type === NetworkType::WIREGUARD) {
            $this->sync->resyncMembers($network, $member->id);
        } else {
            $this->firewall->handle($network);
        }

        $this->recompute->handle($network);
    }
}
