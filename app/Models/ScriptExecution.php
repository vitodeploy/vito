<?php

namespace App\Models;

use App\Enums\ScriptExecutionStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScriptExecution extends AbstractModel
{
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

    public static array $statusColors = [
        ScriptExecutionStatus::EXECUTING => 'warning',
        ScriptExecutionStatus::COMPLETED => 'success',
        ScriptExecutionStatus::FAILED => 'danger',
    ];

    /**
     * @return BelongsTo<Script, $this>
     */
    public function script(): BelongsTo
    {
        return $this->belongsTo(Script::class);
    }

    public function getContent(): string
    {
        $content = $this->script->content;
        foreach ($this->variables as $variable => $value) {
            if (is_string($value) && ! empty($value)) {
                $content = str_replace('${'.$variable.'}', $value, $content);
            }
        }

        return $content;
    }

    /**
     * @return BelongsTo<ServerLog, $this>
     */
    public function serverLog(): BelongsTo
    {
        return $this->belongsTo(ServerLog::class);
    }

    /**
     * @return BelongsTo<Server, $this>
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
