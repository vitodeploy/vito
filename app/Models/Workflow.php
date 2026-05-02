<?php

namespace App\Models;

use App\DTOs\WorkflowActionDTO;
use App\WorkflowActions\WorkflowActionInterface;
use Database\Factories\WorkflowFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $user_id
 * @property int|null $project_id
 * @property string $name
 * @property array|null $payload
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read User|null $user
 * @property-read Project|null $project
 * @property-read Collection<int, WorkflowRun> $runs
 */
class Workflow extends Model
{
    /** @use HasFactory<WorkflowFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'project_id',
        'name',
        'payload',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'project_id' => 'integer',
        'payload' => 'json',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function runs(): HasMany
    {
        return $this->hasMany(WorkflowRun::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function actions(): array
    {
        $actions = config('workflow.actions', []);
        foreach ($actions as $actionKey => $action) {
            $handlerClass = $action['handler'] ?? null;
            if ($handlerClass && class_exists($handlerClass)) {
                /** @var WorkflowActionInterface $handler */
                $handler = new $handlerClass($this->user, $this);
                $action['inputs'] = $handler->inputs();
                $action['outputs'] = $handler->outputs();
                $actions[$actionKey] = $action;
            }
        }

        return $actions;
    }

    public function getStartingNode(): ?WorkflowActionDTO
    {
        $payload = $this->payload ?? [];

        $startingNode = null;

        $nodes = data_get($payload, 'nodes', []);

        foreach ($nodes as $node) {
            if (data_get($node, 'data.action.starting') === true) {
                $startingNode = $node;
                break;
            }
        }

        if (! $startingNode) {
            return null;
        }

        $actionData = $startingNode['data']['action'] ?? [];
        $nodeId = $startingNode['id'];

        return WorkflowActionDTO::fromArray($actionData, $nodeId);
    }

    public function getExecutionTree(): ?WorkflowActionDTO
    {
        $payload = $this->payload ?? [];

        $nodes = data_get($payload, 'nodes', []);
        $edges = data_get($payload, 'edges', []);

        if (empty($nodes)) {
            return null;
        }

        // Find the starting node
        $startingNode = null;
        foreach ($nodes as $node) {
            if (data_get($node, 'data.action.starting') === true) {
                $startingNode = $node;
                break;
            }
        }

        if (! $startingNode) {
            return null;
        }

        // Build the execution tree recursively
        return $this->buildExecutionTree($startingNode, $nodes, $edges);
    }

    /**
     * Build the execution tree recursively starting from a given node
     */
    private function buildExecutionTree(array $currentNode, array $allNodes, array $allEdges): WorkflowActionDTO
    {
        $nodeId = $currentNode['id'];
        $actionData = $currentNode['data']['action'] ?? [];

        // Create the base DTO for this node
        $dto = WorkflowActionDTO::fromArray($actionData, $nodeId);

        // Find all edges that start from this node
        $outgoingEdges = array_filter($allEdges, function ($edge) use ($nodeId) {
            return $edge['source'] === $nodeId;
        });

        $successDto = null;
        $failureDto = null;

        foreach ($outgoingEdges as $edge) {
            $targetNodeId = $edge['target'];
            $edgeStatus = $edge['data']['status'] ?? 'success';

            // Find the target node
            $targetNode = null;
            foreach ($allNodes as $node) {
                if ($node['id'] === $targetNodeId) {
                    $targetNode = $node;
                    break;
                }
            }

            if ($targetNode) {
                // Recursively build the subtree for the target node
                $subTree = $this->buildExecutionTree($targetNode, $allNodes, $allEdges);

                // Assign to the appropriate branch based on edge status
                if ($edgeStatus === 'success') {
                    $successDto = $subTree;
                } elseif ($edgeStatus === 'failure') {
                    $failureDto = $subTree;
                }
            }
        }

        // Return a new DTO with the success and failure branches
        return new WorkflowActionDTO(
            label: $dto->label,
            handler: $dto->handler,
            outputs: $dto->outputs,
            inputs: $dto->inputs,
            id: $dto->id,
            success: $successDto,
            failure: $failureDto,
        );
    }
}
