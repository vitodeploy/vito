<?php

namespace App\Models;

use App\Enums\CommandExecutionStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $command_id
 * @property int $server_id
 * @property int $user_id
 * @property ?int $server_log_id
 * @property array<mixed> $variables
 * @property string $status
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Command $command
 * @property ?ServerLog $serverLog
 * @property Server $server
 * @property ?User $user
 */
class CommandExecution extends AbstractModel
{
    /** @use HasFactory<\Database\Factories\CommandExecutionFactory> */
    use HasFactory;

    protected $fillable = [
        'command_id',
        'server_id',
        'user_id',
        'server_log_id',
        'variables',
        'status',
    ];

    protected $casts = [
        'command_id' => 'integer',
        'server_id' => 'integer',
        'user_id' => 'integer',
        'server_log_id' => 'integer',
        'variables' => 'array',
    ];

    /**
     * @var array<string, string>
     */
    public static array $statusColors = [
        CommandExecutionStatus::EXECUTING => 'warning',
        CommandExecutionStatus::COMPLETED => 'success',
        CommandExecutionStatus::FAILED => 'danger',
    ];

    /**
     * @return BelongsTo<Command, covariant $this>
     */
    public function command(): BelongsTo
    {
        return $this->belongsTo(Command::class);
    }

    public function getContent(): string
    {
        $content = $this->command->command;
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

    /**
     * @return BelongsTo<User, covariant $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
