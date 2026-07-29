<?php

namespace App\Http\Resources;

use App\Models\NetworkPeer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin NetworkPeer */
class NetworkPeerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'network_id' => $this->network_id,
            'name' => $this->name,
            'ip' => $this->ip,
            'public_key' => $this->public_key,
            'status' => $this->status->getText(),
            'status_color' => $this->status->getColor(),
            'last_handshake_at' => $this->last_handshake_at,
            'byo' => $this->byo,
            'has_private_key' => $this->hasPrivateKey(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
