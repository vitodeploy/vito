<?php

namespace App\Http\Resources;

use App\Models\Project;
use App\Models\User;
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
        /** @var ?User $user */
        $user = $request->user();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'role' => $user ? $this->role($user)->value : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'users' => ProjectUserResource::collection($this->whenLoaded('users')),
        ];
    }
}
