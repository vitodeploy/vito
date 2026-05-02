<?php

namespace App\Http\Resources;

use App\Models\DNSProvider;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DNSProvider */
class DNSProviderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'provider' => $this->provider,
            'connected' => $this->connected,
            'project_id' => $this->project_id,
            'global' => is_null($this->project_id),
            'editable_data' => $this->provider()->editableData(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
