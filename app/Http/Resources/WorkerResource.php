<?php

namespace App\Http\Resources;

use App\Models\Worker;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Worker */
class WorkerResource extends JsonResource
{
    /**
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'server_id' => $this->server_id,
            'site_id' => $this->site_id,
            $this->mergeWhen(! $request->is('api/*'), [
                'component_id' => $this->component_id,
                'managed' => $this->component_capability_id !== null,
                'component_url' => $this->whenLoaded('component', fn (): string => route('components.show', [$this->component->environment->application_id, $this->component->environment_id, $this->component->id], false)),
            ]),
            'name' => $this->name,
            'command' => $this->command,
            'user' => $this->user,
            'auto_start' => $this->auto_start,
            'auto_restart' => $this->auto_restart,
            'numprocs' => $this->numprocs,
            'status' => $this->status->getText(),
            'status_color' => $this->status->getColor(),
            'error' => $this->error,
            'is_site_bootstrap' => $this->isSiteBootstrap(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
