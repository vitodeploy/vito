<?php

namespace App\Http\Resources;

use App\Models\Service;
use App\Services\SupportsNetworking;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;

/** @mixin Service */
class ServiceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $handler = $this->hasHandler() ? $this->handler() : null;

        return [
            'id' => $this->id,
            'server_id' => $this->server_id,
            'type' => $this->type,
            'type_data' => $this->type_data !== null ? Arr::except($this->type_data, ['secret']) : null,
            'config_paths' => config("service.services.{$this->name}.config_paths", []),
            'name' => $this->name,
            'version' => $this->version,
            'installed_version' => $this->installed_version,
            'unit' => $this->unit,
            'status' => $this->status->getText(),
            'status_color' => $this->status->getColor(),
            'icon' => config('core.service_icons')[$this->name] ?? '',
            'is_default' => $this->is_default,
            'supports_networking' => $handler instanceof SupportsNetworking,
            'networking_enabled' => $handler instanceof SupportsNetworking && $handler->networkingEnabled(),
            'networking_managed' => $handler instanceof SupportsNetworking && $handler->networkingManaged(),
            'networking_effective' => $handler instanceof SupportsNetworking ? ($this->type_data['networking_effective'] ?? null) : null,
            'networking_checked_at' => $handler instanceof SupportsNetworking ? ($this->type_data['networking_checked_at'] ?? null) : null,
            'log' => $this->log ? new ServerLogResource($this->log) : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
