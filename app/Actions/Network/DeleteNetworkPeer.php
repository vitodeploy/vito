<?php

namespace App\Actions\Network;

use App\DTOs\SocketEventDTO;
use App\Events\SocketEvent;
use App\Models\NetworkPeer;

class DeleteNetworkPeer
{
    public function __construct(
        private DispatchNetworkServerSync $sync,
        private RecomputeNetworkStatus $recompute,
    ) {}

    public function delete(NetworkPeer $peer): void
    {
        $network = $peer->network;
        $peerId = $peer->id;

        $peer->delete();

        $this->sync->resyncMembers($network);
        $this->recompute->handle($network);

        SocketEvent::dispatch(new SocketEventDTO(
            projectId: $network->project_id,
            type: 'network-peer.deleted',
            data: ['id' => $peerId],
        ));
    }
}
