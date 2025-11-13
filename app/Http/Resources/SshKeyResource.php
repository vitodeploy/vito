<?php

namespace App\Http\Resources;

use App\Models\SshKey;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SshKey */
class SshKeyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => new UserResource($this->whenLoaded('user')),
            'name' => $this->name,
            'deployment_user' => $this->whenPivotLoaded('server_ssh_keys', function () {
                return $this->pivot->user ?? null;
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
