<?php

namespace App\Http\Resources;

use App\Models\NetworkServer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin NetworkServer */
class NetworkServerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'network_id' => $this->network_id,
            'server_id' => $this->server_id,
            'server_name' => $this->whenLoaded('server', fn (): string => $this->server->name),
            'ip' => $this->ip,
            'private_ip' => $this->whenLoaded('serverIpAddress', fn (): ?string => $this->serverIpAddress?->ip),
            'public_key' => $this->public_key,
            'status' => $this->status->getText(),
            'status_color' => $this->status->getColor(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
