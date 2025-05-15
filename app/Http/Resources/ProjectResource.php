<?php

namespace App\Http\Resources;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Project */
class ProjectResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'users' => UserResource::collection($this->users),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'created_at_by_timezone' => $this->created_at_by_timezone,
            'updated_at_by_timezone' => $this->updated_at_by_timezone,
        ];
    }
}
