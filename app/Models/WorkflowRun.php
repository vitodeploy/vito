<?php

namespace App\Models;

use App\Enums\WorkflowRunStatus;
use Database\Factories\WorkflowRunFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int|null $workflow_id
 * @property int|null $user_id
 * @property string|null $log_disk
 * @property string|null $log_path
 * @property string|null $current_node_id
 * @property string|null $current_node_label
 * @property WorkflowRunStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Workflow|null $workflow
 */
class WorkflowRun extends Model
{
    /** @use HasFactory<WorkflowRunFactory> */
    use HasFactory;

    protected $fillable = [
        'workflow_id',
        'user_id',
        'log_disk',
        'log_path',
        'current_node_id',
        'current_node_label',
        'status',
        'verbose',
    ];

    protected $casts = [
        'workflow_id' => 'integer',
        'user_id' => 'integer',
        'logs' => 'json',
        'status' => WorkflowRunStatus::class,
        'verbose' => 'boolean',
    ];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    public function log(string $content): void
    {
        if (empty($this->log_disk) || empty($this->log_path)) {
            $this->log_disk = 'server-logs';
            $this->log_path = 'workflow_run_'.$this->id.'.log';
            $this->save();
        }

        $logEntry = '['.now()->toDateTimeString().'] '.PHP_EOL.$content.PHP_EOL;

        Storage::disk($this->log_disk)->append($this->log_path, $logEntry);
    }

    public function getLogContent(): string
    {
        if (empty($this->log_disk) || empty($this->log_path) || ! Storage::disk($this->log_disk)->exists($this->log_path)) {
            return "Log file doesn't exist or is empty!";
        }

        return Storage::disk($this->log_disk)->get($this->log_path);
    }
}
