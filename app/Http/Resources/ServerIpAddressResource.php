<?php

namespace App\Http\Resources;

use App\Models\ServerIpAddress;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ServerIpAddress */
class ServerIpAddressResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'server_id' => $this->server_id,
            'ip' => $this->ip,
            'prefix_length' => $this->prefix_length,
            'family' => $this->family->getText(),
            'interface' => $this->interface,
            'type' => $this->type->getText(),
            'type_color' => $this->type->getColor(),
            'status' => $this->status->getText(),
            'status_color' => $this->status->getColor(),
            'is_managed' => $this->is_managed,
            'is_primary' => $this->is_primary,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
