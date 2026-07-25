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
     * Custom networks a server joined with this address, collected before it is deleted. The
     * membership's foreign key is nullOnDelete, so afterwards nothing records which address the
     * membership announced.
     *
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
     * A custom membership is addressed solely by that row, so losing the address leaves it with
     * nothing to announce — it is removed rather than re-applied with an incomplete source.
     *
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
                ->each(fn (NetworkServer $member) => $this->remove->remove($member));
        });
    }
}
