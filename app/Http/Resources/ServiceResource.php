<?php

namespace App\Http\Resources;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Service */
class ServiceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'server_id' => $this->server_id,
            'type' => $this->type,
            'type_data' => $this->type_data,
            'config_paths' => config("service.services.{$this->name}.config_paths", []),
            'name' => $this->name,
            'version' => $this->version,
            'installed_version' => $this->installed_version,
            'unit' => $this->unit,
            'status' => $this->status->getText(),
            'status_color' => $this->status->getColor(),
            'icon' => config('core.service_icons')[$this->name] ?? '',
            'is_default' => $this->is_default,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
