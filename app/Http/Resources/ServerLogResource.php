<?php

namespace App\Http\Resources;

use App\Models\ServerLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ServerLog */
class ServerLogResource extends JsonResource
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
            'site_id' => $this->site_id,
            'network_id' => $this->network_id,
            'type' => $this->type,
            'name' => $this->name,
            'disk' => $this->disk,
            'is_remote' => $this->is_remote,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
