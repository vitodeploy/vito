<?php

namespace App\Http\Resources;

use App\Models\ScriptEventHook;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ScriptEventHook */
class ScriptEventHookResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'script_id' => $this->script_id,
            'user_id' => $this->user_id,
            'project_id' => $this->project_id,
            'server_id' => $this->server_id,
            'server' => new ServerResource($this->whenLoaded('server')),
            'event' => $this->event->getText(),
            'event_value' => $this->event->value,
            'event_color' => $this->event->getColor(),
            'user' => $this->user,
            'enabled' => $this->enabled,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
