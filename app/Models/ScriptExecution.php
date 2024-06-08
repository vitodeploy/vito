<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $script_id
 * @property int $server_log_id
 * @property string $user
 * @property array $variables
 * @property string $status
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Script $script
 * @property ?ServerLog $serverLog
 */
class ScriptExecution extends Model
{
    use HasFactory;

    protected $fillable = [
        'script_id',
        'server_log_id',
        'user',
        'variables',
        'status',
    ];

    protected $casts = [
        'script_id' => 'integer',
        'server_log_id' => 'integer',
        'variables' => 'array',
    ];

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

    public function serverLog(): BelongsTo
    {
        return $this->belongsTo(ServerLog::class);
    }
}
