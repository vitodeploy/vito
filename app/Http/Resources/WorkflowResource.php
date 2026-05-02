<?php

namespace App\Http\Resources;

use App\DTOs\WorkflowActionDTO;
use App\Models\Workflow;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Workflow */
class WorkflowResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var WorkflowActionDTO|null $startingNode */
        $startingNode = $this->getStartingNode();

        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'nodes' => $this->payload['nodes'] ?? [],
            'edges' => $this->payload['edges'] ?? [],
            'run_inputs' => $startingNode->inputs ?? [],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
