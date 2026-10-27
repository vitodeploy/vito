<?php

namespace App\Http\Resources;

use App\Models\EnvironmentAttachment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin EnvironmentAttachment */
class EnvironmentAttachmentResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'environment_id' => $this->environment_id,
            'alias' => $this->alias,
            'kind' => $this->kind(),
            'name' => $this->attachable?->name,
            'server_id' => $this->server_id,
            'server_name' => $this->server->name,
            'status' => $this->status->getText(),
            'status_color' => $this->status->getColor(),
            'last_error' => $this->last_error,
        ];
    }
}
