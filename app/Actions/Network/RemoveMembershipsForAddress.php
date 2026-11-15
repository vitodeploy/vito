<?php

namespace App\Actions\Network;

use App\Enums\NetworkType;
use App\Models\NetworkServer;
use App\Models\ServerIpAddress;
use Illuminate\Support\Facades\DB;

class RemoveMembershipsForAddress
{
    public function __construct(private RemoveServerFromNetwork $remove) {}

    /**
     * @return array<int, int>
     */
    public function capture(ServerIpAddress $address): array
    {
        return NetworkServer::query()
            ->where('server_ip_address_id', $address->id)
            ->whereHas('network', fn ($query) => $query->where('type', NetworkType::CUSTOM))
            ->pluck('network_id')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, int>  $networkIds
     */
    public function handle(int $serverId, array $networkIds): void
    {
        if ($networkIds === []) {
            return;
        }

        DB::afterCommit(function () use ($serverId, $networkIds): void {
            NetworkServer::query()
                ->whereIn('network_id', $networkIds)
                ->where('server_id', $serverId)
                ->get()
                ->each(fn (NetworkServer $member) => $this->remove->remove($member, observed: true));
        });
    }
}
