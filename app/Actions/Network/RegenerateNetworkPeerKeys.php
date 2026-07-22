<?php

namespace App\Actions\Network;

use App\DTOs\SocketEventDTO;
use App\Enums\NetworkPeerStatus;
use App\Events\SocketEvent;
use App\Http\Resources\NetworkPeerResource;
use App\Models\NetworkPeer;
use App\ValidationRules\WireGuardPublicKeyRule;
use Illuminate\Support\Facades\Validator;

class RegenerateNetworkPeerKeys
{
    public function __construct(
        private GenerateWireGuardKeys $keys,
        private DispatchNetworkServerSync $sync,
        private RecomputeNetworkStatus $recompute,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function regenerate(NetworkPeer $peer, array $input): void
    {
        $network = $peer->network;

        Validator::make($input, [
            'public_key' => ['nullable', 'string', new WireGuardPublicKeyRule($network, $peer->id)],
        ])->validate();

        $publicKey = $input['public_key'] ?? null;

        if (is_string($publicKey) && $publicKey !== '') {
            $peer->fill(['public_key' => $publicKey, 'private_key' => null, 'byo' => true]);
        } else {
            $keys = $this->keys->generate();
            $peer->fill(['public_key' => $keys['public_key'], 'private_key' => $keys['private_key'], 'byo' => false]);
        }

        $peer->status = NetworkPeerStatus::PENDING;
        $peer->last_handshake_at = null;
        $peer->save();

        $this->sync->resyncMembers($network);
        $this->recompute->handle($network);

        SocketEvent::dispatch(new SocketEventDTO(
            projectId: $network->project_id,
            type: 'network-peer.updated',
            data: new NetworkPeerResource($peer),
        ));
    }
}
