<?php

namespace App\Actions\Network;

use App\Enums\NetworkStatus;
use App\Enums\NetworkType;
use App\Models\Network;
use App\Models\Server;
use Illuminate\Support\Facades\DB;

class DeleteNetwork
{
    public function __construct(
        private DispatchNetworkServerSync $sync,
        private RecomputeNetworkStatus $recompute,
    ) {}

    public function delete(Network $network, bool $observed = false): void
    {
        DB::transaction(function () use ($network, $observed): void {
            Server::query()->whereIn('id', $network->servers()->select('server_id'))->orderBy('id')->lockForUpdate()->get();
            if (! $observed || $network->type !== NetworkType::PROVIDER) {
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
