<?php

namespace App\Actions\Network;

use App\Enums\NetworkServerStatus;
use App\Enums\NetworkType;
use App\Models\Network;
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
            $network->load('servers.server');
            foreach ($network->servers as $sibling) {
                if ($sibling->id !== $member->id
                    && in_array($sibling->status, [NetworkServerStatus::ACTIVE, NetworkServerStatus::UPDATING], true)) {
                    $this->sync->toPresent($sibling);
                }
            }
        } elseif ($network->firewall_enabled) {
            $this->firewall->handle($network);
        }

        $this->recompute->handle($network);
    }
}
