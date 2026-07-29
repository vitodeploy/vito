<?php

namespace App\Actions\Network;

use App\Enums\NetworkServerStatus;
use App\Enums\NetworkType;
use App\Jobs\Network\SyncProviderNetworksJob;
use App\Models\Network;
use App\Models\NetworkServer;

class SyncNetwork
{
    public function __construct(
        private DispatchNetworkServerSync $sync,
        private RecomputeNetworkStatus $recompute,
    ) {}

    public function network(Network $network): void
    {
        if ($network->type === NetworkType::PROVIDER) {
            SyncProviderNetworksJob::dispatchUnlessRecent($network->project, $network);

            return;
        }

        $network->load('servers.server');
        foreach ($network->servers as $member) {
            if ($member->status !== NetworkServerStatus::LEAVING) {
                $member->sync_attempts = 0;
                $member->save();
                $this->sync->toPresent($member);
            }
        }

        $this->recompute->handle($network);
    }

    public function member(NetworkServer $member): void
    {
        if ($member->status === NetworkServerStatus::LEAVING) {
            return;
        }

        $member->sync_attempts = 0;
        $member->save();
        $this->sync->toPresent($member);
        $this->recompute->handle($member->network);
    }
}
