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
            'site_id' => $this->site_id,
            'type' => $this->type,
            'name' => $this->name,
            'disk' => $this->disk,
            'is_remote' => $this->is_remote,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'created_at_by_timezone' => $this->created_at_by_timezone,
            'updated_at_by_timezone' => $this->updated_at_by_timezone,
        ];
    }
}
