<?php

namespace App\Actions\Network;

use App\DTOs\SocketEventDTO;
use App\Enums\FirewallRuleStatus;
use App\Events\SocketEvent;
use App\Models\Server;
use App\Models\ServerNetworkRule;
use Illuminate\Support\Facades\DB;

class FinalizeServerNetworkRules
{
    /**
     * The full ruleset was pushed successfully: the emitted rows are now live,
     * and the rows that were dropped from the emit set are gone from the box.
     *
     * @param  array<int, int>  $emittedIds
     * @param  array<int, int>  $deletingIds
     */
    public function success(Server $server, array $emittedIds, array $deletingIds): void
    {
        DB::transaction(function () use ($emittedIds, $deletingIds): void {
            if ($emittedIds !== []) {
                ServerNetworkRule::query()
                    ->whereIn('id', $emittedIds)
                    ->where('status', '!=', FirewallRuleStatus::READY)
                    ->update(['status' => FirewallRuleStatus::READY]);
            }

            if ($deletingIds !== []) {
                ServerNetworkRule::query()->whereIn('id', $deletingIds)->delete();
            }
        });

        $this->broadcast($server);
    }

    /**
     * @param  array<int, int>  $emittedIds
     */
    public function failure(Server $server, array $emittedIds): void
    {
        if ($emittedIds !== []) {
            ServerNetworkRule::query()
                ->whereIn('id', $emittedIds)
                ->whereIn('status', [FirewallRuleStatus::CREATING, FirewallRuleStatus::UPDATING])
                ->update(['status' => FirewallRuleStatus::FAILED]);
        }

        $this->broadcast($server);
    }

    private function broadcast(Server $server): void
    {
        SocketEvent::dispatch(new SocketEventDTO(
            projectId: $server->project_id,
            type: 'server-network-rule.updated',
            data: ['server_id' => $server->id],
        ));
    }
}
