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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReconcileNetworksCommand extends Command
{
    protected $signature = 'networks:reconcile';

    protected $description = 'Drive pending/failed/stuck network member syncs for reachable servers and reap stalled teardowns.';

    private const MAX_ATTEMPTS = 5;

    private const SLOW_RETRY_MINUTES = 60;

    private const MAX_LEAVING_ATTEMPTS = 30;

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

    /**
     * The first attempts run at the command's own cadence; past that the member drops to an
     * hourly retry rather than being abandoned. A member that gave up permanently would keep
     * its network `failed` forever and would never pick up a later change — a moved WireGuard
     * port, say — with nothing in the UI to say a manual sync is required.
     */
    private function reconcileFailed(): void
    {
        $slowRetry = now()->subMinutes(self::SLOW_RETRY_MINUTES);

        $due = fn ($query) => $query->where(fn ($due) => $due
            ->where('sync_attempts', '<', self::MAX_ATTEMPTS)
            ->orWhere('updated_at', '<', $slowRetry));

        NetworkServer::query()
            ->where('status', NetworkServerStatus::FAILED)
            ->tap($due)
            ->whereHas('server', $this->reachable())
            ->pluck('id')
            ->each(function (int $id) use ($due): void {
                $claimed = NetworkServer::query()
                    ->whereKey($id)
                    ->where('status', NetworkServerStatus::FAILED)
                    ->tap($due)
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

    /**
     * On-server cleanup only works while the server is reachable, so a teardown keeps retrying —
     * quickly at first, then hourly — for about a day before the membership is force-removed.
     * Giving up after the fast attempts alone would abandon the tunnel config on a server that
     * was merely rebooting.
     */
    private function reconcileLeaving(): void
    {
        $threshold = now()->subMinutes(2);
        $slowRetry = now()->subMinutes(self::SLOW_RETRY_MINUTES);

        NetworkServer::query()
            ->where('status', NetworkServerStatus::LEAVING)
            ->where('updated_at', '<', $threshold)
            ->with('network', 'server')
            ->get()
            ->each(function (NetworkServer $member) use ($threshold, $slowRetry): void {
                if ($member->sync_attempts >= self::MAX_LEAVING_ATTEMPTS) {
                    $this->forceConverge($member);

                    return;
                }

                if ($member->sync_attempts >= self::MAX_ATTEMPTS
                    && $member->updated_at instanceof Carbon
                    && $member->updated_at->greaterThan($slowRetry)) {
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
            'On-server cleanup could not complete after repeated attempts; membership force-removed. '
            .'The tunnel configuration may still exist on the server; it is cleared the next time the server syncs a network.'
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
