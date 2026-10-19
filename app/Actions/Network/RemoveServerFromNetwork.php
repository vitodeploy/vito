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

    public function remove(NetworkServer $member, bool $observed = false): void
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($member, $observed): void {
            \App\Models\Server::query()->lockForUpdate()->findOrFail($member->server_id);
            $member->refresh();
            $network = $member->network;
            if (! $observed || ($network->type !== NetworkType::PROVIDER
                && ! ($network->type === NetworkType::CUSTOM && $member->server_ip_address_id === null))) {
                app(CheckLoadBalancerNetwork::class)->validate($member);
            }

            $this->sync->teardown($member);

            if ($network->type === NetworkType::WIREGUARD) {
                $this->sync->resyncMembers($network, $member->id);
            } else {
                $this->firewall->handle($network);
            }

            $this->recompute->handle($network);
        });
    }
}
