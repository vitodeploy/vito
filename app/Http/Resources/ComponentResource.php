<?php

namespace App\Http\Resources;

use App\Models\Component;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Component */
class ComponentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'environment_id' => $this->environment_id,
            'environment_status' => $this->whenLoaded('environment', fn (): string => $this->environment->status->getText()),
            'application_status' => $this->whenLoaded('environment', fn (): string => $this->environment->application->status->getText()),
            'server_id' => $this->server_id,
            'server_name' => $this->whenLoaded('server', fn (): string => $this->server->name),
            'name' => $this->name,
            'type' => $this->type->getText(),
            'type_color' => $this->type->getColor(),
            'user' => $this->user,
            'path' => $this->path,
            'status' => $this->status->getText(),
            'status_color' => $this->status->getColor(),
            'last_error' => $this->last_error,
            'needs_redeploy' => $this->needs_redeploy,
            'ssh_key' => $this->ssh_key,
            'capabilities' => $this->whenLoaded('capabilities', fn (): array => $this->capabilities->pluck('capability')->all()),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
