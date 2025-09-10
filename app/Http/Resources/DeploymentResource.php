<?php

namespace App\Http\Resources;

use App\Models\Deployment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Deployment */
class DeploymentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'site_id' => $this->site_id,
            'deployment_script_id' => $this->deployment_script_id,
            'log_id' => $this->log_id,
            'log' => new ServerLogResource($this->log),
            'commit_id' => $this->commit_id,
            'commit_id_short' => $this->commit_id_short,
            'commit_data' => $this->commit_data,
            'status' => $this->status->getText(),
            'status_color' => $this->status->getColor(),
            'release' => $this->release,
            'active' => $this->active,
            'updated_at' => $this->updated_at,
            'created_at' => $this->created_at,
        ];
    }
}
