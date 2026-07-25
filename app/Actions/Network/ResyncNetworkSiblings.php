<?php

namespace App\Actions\Network;

use App\Enums\NetworkServerStatus;
use App\Models\NetworkServer;
use App\Models\Server;
use Illuminate\Support\Facades\DB;

class ResyncNetworkSiblings
{
    public function __construct(private DispatchNetworkServerSync $sync) {}

    /**
     * Members of every network a server belongs to, collected before its membership rows cascade
     * away. Every network type needs this, not just WireGuard: a network without a CIDR — and
     * every provider network — derives per-member host rules, so the remaining members keep an
     * allow rule for a departed server's address until they re-materialise, and that address can
     * later be reassigned to an unrelated host.
     *
     * @return array<int, int>
     */
    public function capture(Server $server): array
    {
        return NetworkServer::query()
            ->whereIn(
                'network_id',
                NetworkServer::query()->where('server_id', $server->id)->select('network_id')
            )
            ->where('server_id', '!=', $server->id)
            ->where('status', '!=', NetworkServerStatus::LEAVING)
            ->pluck('id')
            ->all();
    }

    /**
     * Deferred to after commit so the members are rewritten against the topology that survives
     * the deletion rather than the one still inside the open transaction.
     *
     * @param  array<int, int>  $memberIds
     */
    public function handle(array $memberIds): void
    {
        if ($memberIds === []) {
            return;
        }

        DB::afterCommit(function () use ($memberIds): void {
            NetworkServer::query()
                ->whereIn('id', $memberIds)
                ->where('status', '!=', NetworkServerStatus::LEAVING)
                ->with('server', 'network')
                ->get()
                ->each(fn (NetworkServer $member) => $this->sync->toPresent($member));
        });
    }
}
