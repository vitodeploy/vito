<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnvironmentVariableResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource['id'],
            'key' => $this->resource['key'],
            'value' => $this->resource['is_secret'] ? null : $this->resource['value'],
            'is_secret' => $this->resource['is_secret'],
            'has_value' => true,
            'source' => $this->resource['source'],
            'component_name' => $this->resource['component_name'],
        ];
    }
}
