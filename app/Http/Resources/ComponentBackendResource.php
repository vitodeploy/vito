<?php

namespace App\Http\Resources;

use App\Models\ComponentBackend;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ComponentBackend */
class ComponentBackendResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'target_component_id' => $this->target_component_id,
            'target_name' => $this->whenLoaded('targetComponent', fn (): ?string => $this->targetComponent?->name),
            'ip' => $this->ip,
            'port' => $this->port,
            'weight' => $this->weight,
            'backup' => $this->backup,
        ];
    }
}
