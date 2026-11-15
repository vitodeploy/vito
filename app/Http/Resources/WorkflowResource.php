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
        $reveal = $request->user()?->can('revealInputs', $this->resource) === true;

        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'nodes' => array_map(function (mixed $node) use ($reveal): mixed {
                if (is_array($node) && data_get($node, 'data.action') !== null) {
                    data_set($node, 'data.action.inputs', Workflow::visibleInputs(data_get($node, 'data.action.inputs'), $reveal));
                }

                return $node;
            }, $this->payload['nodes'] ?? []),
            'edges' => $this->payload['edges'] ?? [],
            'run_inputs' => Workflow::visibleInputs($startingNode->inputs ?? [], $reveal),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
