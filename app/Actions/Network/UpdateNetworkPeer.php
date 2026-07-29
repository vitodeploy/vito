<?php

namespace App\Actions\Network;

use App\DTOs\SocketEventDTO;
use App\Enums\NetworkPeerStatus;
use App\Events\SocketEvent;
use App\Http\Resources\NetworkPeerResource;
use App\Models\NetworkPeer;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UpdateNetworkPeer
{
    public function __construct(
        private DispatchNetworkServerSync $sync,
        private RecomputeNetworkStatus $recompute,
    ) {}

    /**
     * @param  array<string, mixed>  $input
     */
    public function update(NetworkPeer $peer, array $input): void
    {
        $network = $peer->network;

        Validator::make($input, [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('network_peers', 'name')->where('network_id', $network->id)->ignore($peer->id),
            ],
            'enabled' => ['required', 'boolean'],
        ])->validate();

        $target = $input['enabled']
            ? ($peer->status === NetworkPeerStatus::DISABLED ? NetworkPeerStatus::PENDING : $peer->status)
            : NetworkPeerStatus::DISABLED;

        $statusChanged = $target !== $peer->status;

        $peer->name = $input['name'];
        $peer->status = $target;
        $peer->save();

        if ($statusChanged) {
            $this->sync->resyncMembers($network);
            $this->recompute->handle($network);
        }

        SocketEvent::dispatch(new SocketEventDTO(
            projectId: $network->project_id,
            type: 'network-peer.updated',
            data: new NetworkPeerResource($peer),
        ));
    }
}
