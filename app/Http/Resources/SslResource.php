<?php

namespace App\Http\Resources;

use App\Models\Ssl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Ssl */
class SslResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'server_id' => $this->site->server_id,
            'site_id' => $this->site_id,
            'is_active' => $this->is_active,
            'type' => $this->type,
            'status' => $this->status->getText(),
            'log' => $this->log_id ? ServerLogResource::make($this->log) : null,
            'status_color' => $this->status->getColor(),
            'expires_at' => $this->expires_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
