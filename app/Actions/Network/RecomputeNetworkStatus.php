<?php

namespace App\Actions\Network;

use App\DTOs\SocketEventDTO;
use App\Enums\NetworkPeerStatus;
use App\Enums\NetworkServerStatus;
use App\Enums\NetworkStatus;
use App\Events\SocketEvent;
use App\Http\Resources\NetworkPeerResource;
use App\Http\Resources\NetworkResource;
use App\Models\Network;
use App\Models\NetworkPeer;

class RecomputeNetworkStatus
{
    public function handle(Network $network): void
    {
        $network = $network->fresh();

        if (! $network instanceof Network) {
            return;
        }

        if ($network->status === NetworkStatus::DELETING) {
            if ($network->servers()->count() === 0) {
                $projectId = $network->project_id;
                $id = $network->id;
                $network->delete();

                SocketEvent::dispatch(new SocketEventDTO(
                    projectId: $projectId,
                    type: 'network.deleted',
                    data: ['id' => $id],
                ));
            }

            return;
        }

        $statuses = $network->servers()->pluck('status');

        $computed = match (true) {
            $statuses->isEmpty() => NetworkStatus::CREATING,
            $statuses->contains(NetworkServerStatus::FAILED) => NetworkStatus::FAILED,
            $statuses->contains(NetworkServerStatus::PENDING),
            $statuses->contains(NetworkServerStatus::UPDATING),
            $statuses->contains(NetworkServerStatus::LEAVING) => NetworkStatus::SYNCING,
            default => NetworkStatus::ACTIVE,
        };

        if ($network->status !== $computed) {
            $network->status = $computed;
            $network->save();
        }

        if ($computed === NetworkStatus::ACTIVE) {
            $this->activatePendingPeers($network);
        }

        if ($computed === NetworkStatus::CREATING) {
            $this->holdActivePeers($network);
        }

        SocketEvent::dispatch(new SocketEventDTO(
            projectId: $network->project_id,
            type: 'network.updated',
            data: new NetworkResource($network->load('serverProvider')->loadCount('servers')),
        ));
    }

    /**
     * No member is left to hold the peer's key, so it is not connected to anything. It waits
     * for a server to join rather than reporting itself active against an empty network.
     */
    private function holdActivePeers(Network $network): void
    {
        $network->peers()
            ->where('status', NetworkPeerStatus::ACTIVE)
            ->get()
            ->each(function (NetworkPeer $peer) use ($network): void {
                $peer->status = NetworkPeerStatus::PENDING;
                $peer->save();

                SocketEvent::dispatch(new SocketEventDTO(
                    projectId: $network->project_id,
                    type: 'network-peer.updated',
                    data: new NetworkPeerResource($peer),
                ));
            });
    }

    private function activatePendingPeers(Network $network): void
    {
        $peers = $network->peers()->where('status', NetworkPeerStatus::PENDING)->get();

        foreach ($peers as $peer) {
            $peer->status = NetworkPeerStatus::ACTIVE;
            $peer->save();

            SocketEvent::dispatch(new SocketEventDTO(
                projectId: $network->project_id,
                type: 'network-peer.updated',
                data: new NetworkPeerResource($peer),
            ));
        }
    }
}
