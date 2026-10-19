<?php

namespace App\Actions\Network;

use App\Enums\NetworkStatus;
use App\Models\Network;

class DeleteNetwork
{
    public function __construct(
        private DispatchNetworkServerSync $sync,
        private RecomputeNetworkStatus $recompute,
    ) {}

    public function delete(Network $network, bool $observed = false): void
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($network, $observed): void {
            \App\Models\Server::query()->whereIn('id', $network->servers()->select('server_id'))->orderBy('id')->lockForUpdate()->get();
            if (! $observed || $network->type !== \App\Enums\NetworkType::PROVIDER) {
                foreach ($network->servers()->get() as $member) {
                    app(CheckLoadBalancerNetwork::class)->validate($member);
                    app(CheckEnvironmentResourceNetwork::class)->validate($member);
                }
            }
            $network->status = NetworkStatus::DELETING;
            $network->save();

            $network->load('servers.server');
            foreach ($network->servers as $member) {
                $this->sync->teardown($member);
            }

            $this->recompute->handle($network);
        });
    }
}
