<?php

namespace App\Http\Resources;

use App\Models\Environment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Environment */
class EnvironmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'application_id' => $this->application_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'branch' => $this->branch,
            'runtime_version' => $this->runtime_version,
            'status' => $this->status->getText(),
            'status_color' => $this->status->getColor(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
