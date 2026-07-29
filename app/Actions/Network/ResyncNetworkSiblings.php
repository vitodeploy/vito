<?php

namespace App\Actions\Network;

use App\Enums\NetworkServerStatus;
use App\Models\Network;
use App\Models\NetworkServer;
use App\Models\Server;
use Illuminate\Support\Facades\DB;

class ResyncNetworkSiblings
{
    public function __construct(
        private DispatchNetworkServerSync $sync,
        private RecomputeNetworkStatus $recompute,
    ) {}

    /**
     * The networks a server belongs to, and their other members, collected before its membership
     * rows cascade away. Every network type needs the siblings, not just WireGuard: a network
     * without a CIDR — and every provider network — derives per-member host rules, so the
     * remaining members keep an allow rule for a departed server's address until they
     * re-materialise, and that address can later be reassigned to an unrelated host.
     *
     * The network ids are kept separately because a network losing its last member has no
     * sibling to recompute its status as a side effect.
     *
     * @return array{members: array<int, int>, networks: array<int, int>}
     */
    public function capture(Server $server): array
    {
        $networkIds = NetworkServer::query()
            ->where('server_id', $server->id)
            ->pluck('network_id')
            ->unique()
            ->values()
            ->all();

        $memberIds = NetworkServer::query()
            ->whereIn('network_id', $networkIds)
            ->where('server_id', '!=', $server->id)
            ->where('status', '!=', NetworkServerStatus::LEAVING)
            ->pluck('id')
            ->all();

        return ['members' => $memberIds, 'networks' => $networkIds];
    }

    /**
     * Deferred to after commit so the members are rewritten, and the statuses computed, against
     * the topology that survives the deletion rather than the one still inside the transaction.
     *
     * @param  array{members: array<int, int>, networks: array<int, int>}  $departure
     */
    public function handle(array $departure): void
    {
        if ($departure['networks'] === []) {
            return;
        }

        DB::afterCommit(function () use ($departure): void {
            NetworkServer::query()
                ->whereIn('id', $departure['members'])
                ->where('status', '!=', NetworkServerStatus::LEAVING)
                ->with('server', 'network')
                ->get()
                ->each(fn (NetworkServer $member) => $this->sync->toPresent($member));

            Network::query()
                ->whereIn('id', $departure['networks'])
                ->get()
                ->each(fn (Network $network) => $this->recompute->handle($network));
        });
    }
}
