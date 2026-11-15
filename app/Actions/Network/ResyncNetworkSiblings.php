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
