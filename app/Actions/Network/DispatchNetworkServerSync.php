<?php

namespace App\Actions\Network;

use App\Enums\NetworkServerStatus;
use App\Jobs\Network\SyncNetworkServerJob;
use App\Models\NetworkServer;

class DispatchNetworkServerSync
{
    public function toPresent(NetworkServer $member): void
    {
        if ($member->server->isReady()) {
            $member->status = NetworkServerStatus::UPDATING;
            $member->save();
            dispatch(new SyncNetworkServerJob($member))->onQueue('ssh');

            return;
        }

        $member->status = NetworkServerStatus::PENDING;
        $member->save();
    }

    public function teardown(NetworkServer $member): void
    {
        $member->status = NetworkServerStatus::LEAVING;
        $member->sync_attempts = 0;
        $member->save();

        dispatch(new SyncNetworkServerJob($member, true))->onQueue('ssh');
    }
}
