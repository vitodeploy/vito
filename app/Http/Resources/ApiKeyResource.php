<?php

namespace App\Http\Resources;

use App\Models\PersonalAccessToken;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PersonalAccessToken */
class ApiKeyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'permissions' => collect($this->abilities)
                ->filter(fn (string $ability) => ! str_starts_with($ability, 'project:'))
                ->values()
                ->all(),
            'project_ids' => $this->getProjectIds(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
