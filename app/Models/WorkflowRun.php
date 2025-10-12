<?php

namespace App\Models;

use App\Enums\WorkflowRunStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $workflow_id
 * @property int|null $user_id
 * @property array|null $logs
 * @property string|null $current_node_id
 * @property string|null $current_node_label
 * @property WorkflowRunStatus $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Workflow|null $workflow
 */
class WorkflowRun extends Model
{
    /** @use HasFactory<\Database\Factories\WorkflowRunFactory> */
    use HasFactory;

    protected $fillable = [
        'workflow_id',
        'user_id',
        'logs',
        'current_node_id',
        'current_node_label',
        'status',
    ];

    protected $casts = [
        'workflow_id' => 'integer',
        'user_id' => 'integer',
        'logs' => 'json',
        'status' => WorkflowRunStatus::class,
    ];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }
}
