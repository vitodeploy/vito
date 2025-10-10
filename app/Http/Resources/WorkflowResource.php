<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Workflow */
class WorkflowResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'nodes' => $this->payload['nodes'] ?? [],
            'edges' => $this->payload['edges'] ?? [],
            'is_draft' => $this->is_draft,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
