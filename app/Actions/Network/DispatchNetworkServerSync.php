<?php

namespace App\Actions\Network;

use App\Enums\NetworkServerStatus;
use App\Jobs\Network\SyncNetworkServerJob;
use App\Models\Network;
use App\Models\NetworkServer;
use Illuminate\Support\Facades\DB;

class DispatchNetworkServerSync
{
    /**
     * Re-push the current configuration to every already-provisioned member.
     * Used when peer/topology data changes and existing members must rewrite
     * their WireGuard config and firewall rules.
     */
    public function resyncMembers(Network $network, ?int $excludeMemberId = null): void
    {
        $network->load('servers.server');

        foreach ($network->servers as $member) {
            if ($member->id !== $excludeMemberId
                && in_array($member->status, [NetworkServerStatus::ACTIVE, NetworkServerStatus::UPDATING], true)) {
                $this->toPresent($member);
            }
        }
    }

    public function toPresent(NetworkServer $member): void
    {
        if ($member->server->isReady()) {
            $member->status = NetworkServerStatus::UPDATING;
            $member->save();
            DB::afterCommit(fn () => dispatch(new SyncNetworkServerJob($member))->onQueue('ssh'));

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

        DB::afterCommit(fn () => dispatch(new SyncNetworkServerJob($member, true))->onQueue('ssh'));
    }
}
