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
     * Action inputs can hold credentials (database or admin passwords, HTTP
     * headers), so their values are blanked unless the caller may reveal them.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var WorkflowActionDTO|null $startingNode */
        $startingNode = $this->getStartingNode();
        $reveal = $request->user()?->can('revealInputs', $this->resource) === true;
        $nodes = $this->payload['nodes'] ?? [];

        if (! $reveal) {
            $nodes = array_map(function (mixed $node): mixed {
                if (is_array(data_get($node, 'data.action.inputs'))) {
                    data_set($node, 'data.action.inputs', $this->mask($node['data']['action']['inputs']));
                }

                return $node;
            }, $nodes);
        }

        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'nodes' => $nodes,
            'edges' => $this->payload['edges'] ?? [],
            'run_inputs' => $reveal ? ($startingNode->inputs ?? []) : $this->mask($startingNode->inputs ?? []),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * @param  array<array-key, mixed>  $inputs
     * @return array<array-key, string>
     */
    private function mask(array $inputs): array
    {
        return array_map(fn (): string => '', $inputs);
    }
}
