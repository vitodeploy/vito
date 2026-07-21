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

    public function delete(Network $network): void
    {
        $network->status = NetworkStatus::DELETING;
        $network->save();

        $network->load('servers.server');
        foreach ($network->servers as $member) {
            $this->sync->teardown($member);
        }

        $this->recompute->handle($network);
    }
}
