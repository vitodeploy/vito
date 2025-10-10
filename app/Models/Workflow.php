<?php

namespace App\Models;

use App\WorkflowActions\WorkflowActionInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int|null $user_id
 * @property int|null $project_id
 * @property string $name
 * @property array|null $payload
 * @property bool $is_draft
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read User|null $user
 * @property-read Project|null $project
 * @property-read \Illuminate\Database\Eloquent\Collection<int, WorkflowRun> $runs
 */
class Workflow extends Model
{
    /** @use HasFactory<\Database\Factories\WorkflowFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'project_id',
        'name',
        'payload',
        'is_draft',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'project_id' => 'integer',
        'payload' => 'json',
        'is_draft' => 'boolean',
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
                $handler = new $handlerClass($this);
                if (! isset($action['form']) || empty($action['form'])) {
                    $action['form'] = $handler->form()?->toArray() ?? [];
                }
                $action['outputs'] = $handler->outputs();
                $actions[$actionKey] = $action;
            }
        }

        return $actions;
    }
}
