<?php

namespace App\Jobs\Network;

use App\DTOs\SocketEventDTO;
use App\Enums\NetworkServerStatus;
use App\Enums\ServerStatus;
use App\Enums\ServiceStatus;
use App\Events\SocketEvent;
use App\Http\Resources\NetworkPeerResource;
use App\Models\Network;
use App\Models\NetworkServer;
use App\Models\ServerLog;
use App\Models\Service;
use App\Services\VPN\WireGuard;
use App\Traits\UniqueQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Throwable;

class PollPeerHandshakesJob implements ShouldQueue
{
    use Queueable;
    use UniqueQueue;

    public function __construct(protected Network $network) {}

    public function handle(): void
    {
        $this->run("network-{$this->network->id}-handshakes", function (): void {
            foreach ($this->reachableMembers() as $member) {
                $service = $member->server->service(WireGuard::type());

                if (! $service instanceof Service || $service->status !== ServiceStatus::READY) {
                    continue;
                }

                /** @var WireGuard $handler */
                $handler = $service->handler();

                $this->apply($handler->latestHandshakes($this->network));

                return;
            }
        });
    }

    public function failed(Throwable $e): void
    {
        $member = $this->reachableMembers()->first();
        if ($member instanceof NetworkServer) {
            ServerLog::withNetwork(
                $this->network->id,
                fn () => ServerLog::log($member->server, 'network-handshake-poll-failed', $e->getMessage()),
            );
        }
    }

    /**
     * @param  array<string, int>  $handshakes
     */
    private function apply(array $handshakes): void
    {
        foreach ($this->network->peers()->get() as $peer) {
            $epoch = $handshakes[$peer->public_key] ?? 0;
            if ($epoch <= 0) {
                continue;
            }

            $seenAt = Carbon::createFromTimestamp($epoch);
            if ($peer->last_handshake_at !== null && $peer->last_handshake_at->greaterThanOrEqualTo($seenAt)) {
                continue;
            }

            $peer->last_handshake_at = $seenAt;
            $peer->save();

            SocketEvent::dispatch(new SocketEventDTO(
                projectId: $this->network->project_id,
                type: 'network-peer.updated',
                data: new NetworkPeerResource($peer),
            ));
        }
    }

    /**
     * @return Collection<int, NetworkServer>
     */
    private function reachableMembers(): Collection
    {
        return $this->network->servers()
            ->where('status', NetworkServerStatus::ACTIVE)
            ->whereHas('server', fn ($query) => $query->whereIn('status', [ServerStatus::READY, ServerStatus::UPDATING]))
            ->with('server')
            ->orderBy('id')
            ->get();
    }
}
