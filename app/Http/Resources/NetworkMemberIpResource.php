<?php

namespace App\Http\Resources;

use App\Models\NetworkServer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin NetworkServer */
class NetworkMemberIpResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'server_id' => $this->server_id,
            'server_name' => $this->whenLoaded('server', fn (): string => $this->server->name),
            'ip_address_id' => $this->server_ip_address_id,
            'private_ips' => $this->whenLoaded(
                'server',
                fn () => NetworkPrivateIpResource::collection($this->server->ipAddresses)
            ),
        ];
    }
}
