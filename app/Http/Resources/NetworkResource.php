<?php

namespace App\Http\Resources;

use App\Models\Network;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Network */
class NetworkResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'name' => $this->name,
            'type' => $this->type->getText(),
            'type_value' => $this->type->value,
            'type_color' => $this->type->getColor(),
            'addressing_pool' => $this->addressing_pool->getText(),
            'cidr' => $this->cidr,
            'port' => $this->port,
            'firewall_enabled' => $this->firewall_enabled,
            'status' => $this->status->getText(),
            'status_color' => $this->status->getColor(),
            'servers_count' => $this->whenCounted('servers'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
