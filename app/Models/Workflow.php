<?php

namespace App\Models;

use App\DTOs\DynamicField;
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
use Illuminate\Support\Collection as SupportCollection;
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
    public function getActions(User $user): array
    {
        $actions = config('workflow.actions', []);
        foreach ($actions as $actionKey => $action) {
            $handlerClass = $action['handler'] ?? null;
            if ($handlerClass && class_exists($handlerClass)) {
                /** @var WorkflowActionInterface $handler */
                $handler = new $handlerClass($user, $this);
                $action['fields'] = self::inputFields($handler->inputs());
                $action['outputs'] = $handler->outputs();
                $actions[$actionKey] = $action;
            }
        }

        return $actions;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getStartingFields(User $user): array
    {
        $handlerClass = collect(config('workflow.actions', []))->firstWhere('handler', $this->getStartingNode()?->handler)['handler'] ?? null;

        if (! $handlerClass || ! class_exists($handlerClass)) {
            return [];
        }

        /** @var WorkflowActionInterface $handler */
        $handler = new $handlerClass($user, $this);

        return self::inputFields($handler->inputs());
    }

    /**
     * Normalizes an action's inputs into dynamic fields, converting the legacy `name => description` format.
     *
     * @param  array<int|string, mixed>  $inputs
     * @return list<array<string, mixed>>
     */
    public static function inputFields(array $inputs): array
    {
        $fields = [];
        foreach ($inputs as $name => $input) {
            $fields[] = match (true) {
                $input instanceof DynamicField => $input->toArray(),
                is_array($input) => DynamicField::make((string) $name)->tags()->default([])->toArray(),
                default => DynamicField::make((string) $name)->text()->description(is_scalar($input) ? (string) $input : null)->toArray(),
            };
        }

        return $fields;
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
     * @return array<array-key, mixed>
     */
    public static function visibleInputs(mixed $inputs, bool $reveal): array
    {
        if ($reveal) {
            return is_array($inputs) ? $inputs : [];
        }

        return is_array($inputs) ? array_map(fn (): string => '', $inputs) : [];
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
     * @param  array<string, array<string, mixed>>  $nodes
     * @param  array<string, SupportCollection<int, array<string, mixed>>>  $edges
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
