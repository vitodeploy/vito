<?php

namespace App\Models;

use App\Enums\CommandExecutionStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $command_id
 * @property int $server_log_id
 * @property ?int $server_id
 * @property string $user
 * @property array $variables
 * @property string $status
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Command $command
 * @property ?ServerLog $serverLog
 * @property ?Server $server
 */
class CommandExecution extends AbstractModel
{
    use HasFactory;

    protected $fillable = [
        'command_id',
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
        CommandExecutionStatus::EXECUTING => 'warning',
        CommandExecutionStatus::COMPLETED => 'success',
        CommandExecutionStatus::FAILED => 'danger',
    ];

    public function command(): BelongsTo
    {
        return $this->belongsTo(Command::class);
    }

    public function getContent(): string
    {
        $content = $this->command->command;
        foreach ($this->variables as $variable => $value) {
            if (is_string($value) && ! empty($value)) {
                $content = str_replace('${'.$variable.'}', $value, $content);
            }
        }

        return $content;
    }

    public function serverLog(): BelongsTo
    {
        return $this->belongsTo(ServerLog::class);
    }

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
