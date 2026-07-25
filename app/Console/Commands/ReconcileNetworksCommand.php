<?php

namespace App\Console\Commands;

use App\Actions\Network\ApplyNetworkFirewall;
use App\Actions\Network\DispatchNetworkServerSync;
use App\Actions\Network\RecomputeNetworkStatus;
use App\DTOs\SocketEventDTO;
use App\Enums\NetworkPeerStatus;
use App\Enums\NetworkServerStatus;
use App\Enums\NetworkStatus;
use App\Enums\NetworkType;
use App\Enums\ServerStatus;
use App\Events\SocketEvent;
use App\Jobs\Network\PollPeerHandshakesJob;
use App\Jobs\Network\SyncNetworkServerJob;
use App\Models\Network;
use App\Models\NetworkServer;
use App\Models\ServerLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReconcileNetworksCommand extends Command
{
    protected $signature = 'networks:reconcile';

    protected $description = 'Drive pending/failed/stuck network member syncs for reachable servers and reap stalled teardowns.';

    private const MAX_ATTEMPTS = 5;

    public function handle(): void
    {
        $this->reconcilePending();
        $this->reconcileFailed();
        $this->reconcileStaleUpdating();
        $this->reconcileLeaving();
        $this->pollHandshakes();
    }

    private function pollHandshakes(): void
    {
        Network::query()
            ->where('type', NetworkType::WIREGUARD)
            ->where('status', NetworkStatus::ACTIVE)
            ->whereHas('peers', fn ($query) => $query->where('status', '!=', NetworkPeerStatus::DISABLED))
            ->get()
            ->each(fn (Network $network) => dispatch(new PollPeerHandshakesJob($network))->onQueue('ssh'));
    }

    private function reconcilePending(): void
    {
        NetworkServer::query()
            ->where('status', NetworkServerStatus::PENDING)
            ->whereHas('server', $this->reachable())
            ->pluck('id')
            ->each(function (int $id): void {
                $claimed = NetworkServer::query()
                    ->whereKey($id)
                    ->where('status', NetworkServerStatus::PENDING)
                    ->update(['status' => NetworkServerStatus::UPDATING]);

                if ($claimed === 1) {
                    $this->dispatchSync($id);
                }
            });
    }

    private function reconcileFailed(): void
    {
        NetworkServer::query()
            ->where('status', NetworkServerStatus::FAILED)
            ->where('sync_attempts', '<', self::MAX_ATTEMPTS)
            ->whereHas('server', $this->reachable())
            ->pluck('id')
            ->each(function (int $id): void {
                $claimed = NetworkServer::query()
                    ->whereKey($id)
                    ->where('status', NetworkServerStatus::FAILED)
                    ->where('sync_attempts', '<', self::MAX_ATTEMPTS)
                    ->update([
                        'status' => NetworkServerStatus::UPDATING,
                        'sync_attempts' => DB::raw('sync_attempts + 1'),
                    ]);

                if ($claimed === 1) {
                    $this->dispatchSync($id);
                }
            });
    }

    private function reconcileStaleUpdating(): void
    {
        $threshold = now()->subMinutes(90);

        NetworkServer::query()
            ->where('status', NetworkServerStatus::UPDATING)
            ->where('updated_at', '<', $threshold)
            ->whereHas('server', $this->reachable())
            ->pluck('id')
            ->each(function (int $id) use ($threshold): void {
                $claimed = NetworkServer::query()
                    ->whereKey($id)
                    ->where('status', NetworkServerStatus::UPDATING)
                    ->where('updated_at', '<', $threshold)
                    ->update(['updated_at' => now()]);

                if ($claimed === 1) {
                    $this->dispatchSync($id);
                }
            });
    }

    private function reconcileLeaving(): void
    {
        $threshold = now()->subMinutes(2);

        NetworkServer::query()
            ->where('status', NetworkServerStatus::LEAVING)
            ->where('updated_at', '<', $threshold)
            ->with('network', 'server')
            ->get()
            ->each(function (NetworkServer $member) use ($threshold): void {
                if ($member->sync_attempts >= self::MAX_ATTEMPTS) {
                    $this->forceConverge($member);

                    return;
                }

                $claimed = NetworkServer::query()
                    ->whereKey($member->id)
                    ->where('status', NetworkServerStatus::LEAVING)
                    ->where('updated_at', '<', $threshold)
                    ->update([
                        'sync_attempts' => DB::raw('sync_attempts + 1'),
                        'updated_at' => now(),
                    ]);

                if ($claimed === 1) {
                    dispatch(new SyncNetworkServerJob($member, true))->onQueue('ssh');
                }
            });
    }

    private function forceConverge(NetworkServer $member): void
    {
        $network = $member->network;
        $projectId = $network->project_id;
        $memberId = $member->id;

        ServerLog::withNetwork($network->id, fn () => ServerLog::log(
            $member->server,
            'network-leave-incomplete',
            'On-server cleanup could not complete after repeated attempts; membership force-removed.'
        ));

        $member->delete();

        SocketEvent::dispatch(new SocketEventDTO(
            projectId: $projectId,
            type: 'network-server.deleted',
            data: ['id' => $memberId],
        ));

        if ($network->type === NetworkType::WIREGUARD) {
            app(DispatchNetworkServerSync::class)->resyncMembers($network);
        }

        app(ApplyNetworkFirewall::class)->handle($network);
        app(RecomputeNetworkStatus::class)->handle($network);
    }

    private function dispatchSync(int $id): void
    {
        $member = NetworkServer::find($id);
        if ($member instanceof NetworkServer) {
            dispatch(new SyncNetworkServerJob($member))->onQueue('ssh');
        }
    }

    private function reachable(): callable
    {
        return fn ($query) => $query->whereIn('status', [ServerStatus::READY, ServerStatus::UPDATING]);
    }
}
