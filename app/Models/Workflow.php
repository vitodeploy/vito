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
use Illuminate\Validation\ValidationException;

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
    public function getActions(): array
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

    /**
     * @throws ValidationException
     */
    public function getExecutionTree(): ?WorkflowActionDTO
    {
        $startingNode = $this->getStartingNode();

        if (! $startingNode) {
            return null;
        }

        $nodes = collect(data_get($this->payload, 'nodes', []))->keyBy('id')->all();
        $edges = collect(data_get($this->payload, 'edges', []))->groupBy('source')->all();
        $built = [];

        return $this->buildExecutionTree($startingNode->id, $nodes, $edges, $built, []);
    }

    /**
     * Each node is built once and shared by every branch that reaches it, so
     * reconverging paths cost nothing extra. A path that returns to one of its
     * own ancestors is a cycle and is rejected.
     *
     * @param  array<string, array<string, mixed>>  $nodes
     * @param  array<string, \Illuminate\Support\Collection<int, array<string, mixed>>>  $edges
     * @param  array<string, WorkflowActionDTO>  $built
     * @param  array<string, true>  $ancestors
     *
     * @throws ValidationException
     */
    private function buildExecutionTree(string $nodeId, array $nodes, array $edges, array &$built, array $ancestors): WorkflowActionDTO
    {
        if (isset($ancestors[$nodeId])) {
            throw ValidationException::withMessages(['edges' => 'The workflow contains a cycle.']);
        }

        if (isset($built[$nodeId])) {
            return $built[$nodeId];
        }

        $ancestors[$nodeId] = true;
        $dto = WorkflowActionDTO::fromArray($nodes[$nodeId]['data']['action'] ?? [], $nodeId);

        foreach ($edges[$nodeId] ?? [] as $edge) {
            $target = $edge['target'] ?? null;
            if (! is_scalar($target) || ! isset($nodes[$target])) {
                continue;
            }

            $subTree = $this->buildExecutionTree((string) $target, $nodes, $edges, $built, $ancestors);
            match ($edge['data']['status'] ?? 'success') {
                'success' => $dto->success = $subTree,
                'failure' => $dto->failure = $subTree,
                default => null,
            };
        }

        return $built[$nodeId] = $dto;
    }
}
