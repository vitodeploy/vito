<?php

namespace App\Http\Resources;

use App\Models\Environment;
use App\Models\EnvironmentDeployment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Environment */
class EnvironmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'application_id' => $this->application_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'branch' => $this->branch,
            'auto_deploy' => $this->auto_deploy,
            'runtime_version' => $this->runtime_version,
            'primary_component_id' => $this->primary_component_id,
            'components_count' => $this->whenCounted('components'),
            'needs_redeploy' => (bool) $this->needs_redeploy,
            'live_run_id' => $this->live_run_id,
            'shared_paths' => $this->shared_paths ?? [],
            'keep_releases' => $this->keep_releases,
            'can_deploy' => $request->user()?->can('create', [EnvironmentDeployment::class, $this->resource, $this->application]) ?? false,
            'last_error' => $this->last_error,
            'status' => $this->status->getText(),
            'status_color' => $this->status->getColor(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
