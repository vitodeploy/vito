<?php

namespace App\Http\Resources;

use App\Models\WorkflowRun;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WorkflowRun */
class WorkflowRunResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'workflow_id' => $this->workflow_id,
            'status' => $this->status->getText(),
            'status_color' => $this->status->getColor(),
            'current_node_label' => $this->current_node_label,
            'current_node_id' => $this->current_node_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
