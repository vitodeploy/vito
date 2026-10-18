<?php

namespace App\Http\Resources;

use App\Models\Deployment;
use App\Models\EnvironmentDeployment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin EnvironmentDeployment */
class EnvironmentDeploymentResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'environment_id' => $this->environment_id,
            'status' => $this->status->getText(),
            'status_color' => $this->status->getColor(),
            'current_stage' => $this->current_stage?->getText(),
            'commit_id' => $this->commit_id,
            'commit_data' => $this->commit_data,
            'release' => $this->release,
            'branch' => $this->branch,
            'primary_component_id' => $this->primary_component_id,
            'triggered_by' => $this->whenLoaded('triggeredBy', fn () => $this->triggeredBy?->name),
            'is_join' => $this->is_join,
            'is_recovering' => $this->is_recovering,
            'started_at' => $this->started_at,
            'finished_at' => $this->finished_at,
            'created_at' => $this->created_at,
            'deployments' => $this->whenLoaded('deployments', fn () => $this->deployments->map(fn (Deployment $deployment): array => [
                'id' => $deployment->id,
                'component_id' => $deployment->component_id,
                'component_name' => $deployment->component?->name,
                'server_id' => $deployment->component?->server_id,
                'server_name' => $deployment->component?->server->name,
                'log_id' => $deployment->log_id,
                'current_stage' => $deployment->current_stage?->getText(),
                'status' => $deployment->status->getText(),
                'status_color' => $deployment->status->getColor(),
                'active' => $deployment->active,
            ])),
        ];
    }
}
