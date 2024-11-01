<?php

namespace App\Http\Resources;

use App\Models\SourceControl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SourceControl */
class SourceControlResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'global' => is_null($this->project_id),
            'name' => $this->profile,
            'provider' => $this->provider,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
