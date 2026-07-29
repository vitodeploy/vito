<?php

namespace App\Actions\Network;

use App\DTOs\SocketEventDTO;
use App\Events\SocketEvent;
use App\Http\Resources\NetworkPeerResource;
use App\Models\NetworkPeer;
use Illuminate\Validation\ValidationException;

class ConcealNetworkPeerKey
{
    public function conceal(NetworkPeer $peer): void
    {
        if ($peer->byo) {
            throw ValidationException::withMessages([
                'peer' => __('This peer uses a user-provided key; there is nothing to conceal.'),
            ]);
        }

        $peer->private_key = null;
        $peer->save();

        SocketEvent::dispatch(new SocketEventDTO(
            projectId: $peer->network->project_id,
            type: 'network-peer.updated',
            data: new NetworkPeerResource($peer),
        ));
    }
}
