<?php

namespace App\Actions\Network;

use App\Enums\NetworkServerStatus;
use App\Jobs\Network\ApplyNetworkFirewallJob;
use App\Models\Network;
use App\Models\NetworkServer;
use App\Models\Service;
use Illuminate\Support\Facades\DB;

class ApplyNetworkFirewall
{
    public function __construct(
        private RecomputeNetworkStatus $recompute,
        private MaterializeServerNetworkRules $materialize,
    ) {}

    /**
     * Re-apply the network's firewall on each member. Reachable servers apply
     * now (lightweight, no WireGuard bounce); an unreachable server's member is
     * marked PENDING so the reconciler re-applies when it comes back.
     */
    public function handle(Network $network): void
    {
        $this->materialize->forNetwork($network);

        $deferred = false;

        $network->servers()
            ->where('status', '!=', NetworkServerStatus::LEAVING)
            ->with('server')
            ->get()
            ->each(function (NetworkServer $member) use (&$deferred): void {
                if ($member->server->isReady()) {
                    if ($member->server->firewall() instanceof Service) {
                        DB::afterCommit(fn () => dispatch(new ApplyNetworkFirewallJob($member))->onQueue('ssh'));
                    }

                    return;
                }

                if ($member->status === NetworkServerStatus::ACTIVE) {
                    $member->status = NetworkServerStatus::PENDING;
                    $member->save();
                    $deferred = true;
                }
            });

        if ($deferred) {
            $this->recompute->handle($network);
        }
    }
}
