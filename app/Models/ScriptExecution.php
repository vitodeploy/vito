<?php

namespace App\Models;

use App\Enums\ScriptExecutionStatus;
use Carbon\Carbon;
use Database\Factories\ScriptExecutionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $script_id
 * @property int $server_log_id
 * @property ?int $server_id
 * @property string $user
 * @property array<mixed> $variables
 * @property string $status
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Script $script
 * @property ?ServerLog $serverLog
 * @property ?Server $server
 */
class ScriptExecution extends AbstractModel
{
    /** @use HasFactory<ScriptExecutionFactory> */
    use HasFactory;

    protected $fillable = [
        'script_id',
        'server_id',
        'server_log_id',
        'user',
        'variables',
        'status',
    ];

    protected $casts = [
        'script_id' => 'integer',
        'server_id' => 'integer',
        'server_log_id' => 'integer',
        'variables' => 'array',
    ];

    /**
     * @var array<string, string>
     */
    public static array $statusColors = [
        ScriptExecutionStatus::EXECUTING => 'warning',
        ScriptExecutionStatus::COMPLETED => 'success',
        ScriptExecutionStatus::FAILED => 'danger',
    ];

    /**
     * @return BelongsTo<Script, covariant $this>
     */
    public function script(): BelongsTo
    {
        return $this->belongsTo(Script::class);
    }

    public function getContent(): string
    {
        $content = $this->script->content;
        foreach ($this->variables as $variable => $value) {
            if (is_string($value) && ($value !== '' && $value !== '0')) {
                $content = str_replace('${'.$variable.'}', $value, $content);
            }
        }

        return $content;
    }

    /**
     * @return BelongsTo<ServerLog, covariant $this>
     */
    public function serverLog(): BelongsTo
    {
        return $this->belongsTo(ServerLog::class);
    }

    /**
     * @return BelongsTo<Server, covariant $this>
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function getServer(): ?Server
    {
        if ($this->server_id) {
            return $this->server;
        }

        if ($this->server_log_id) {
            return $this->serverLog?->server;
        }

        return null;
    }
}
