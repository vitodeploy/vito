<?php

namespace App\Http\Resources;

use App\Models\CronJob;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CronJob */
class CronJobResource extends JsonResource
{
    /**
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'server_id' => $this->server_id,
            'site_id' => $this->site_id,
            $this->mergeWhen(! $request->is('api/*'), [
                'component_id' => $this->component_id,
                'managed' => $this->component_capability_id !== null,
                'component_url' => $this->whenLoaded('component', fn (): string => route('components.show', [$this->component->environment->application_id, $this->component->environment_id, $this->component->id], false)),
            ]),
            'command' => $this->command,
            'user' => $this->user,
            'frequency' => $this->frequency,
            'status' => $this->status->getText(),
            'status_color' => $this->status->getColor(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
