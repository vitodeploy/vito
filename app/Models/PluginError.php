<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $plugin_id
 * @property string $error_type
 * @property string $error_message
 * @property string|null $stack_trace
 * @property string|null $file
 * @property int|null $line
 * @property array|null $context
 * @property bool $is_fatal
 * @property Carbon $occurred_at
 * @property Plugin $plugin
 */
class PluginError extends Model
{
    protected $fillable = [
        'plugin_id',
        'error_type',
        'error_message',
        'stack_trace',
        'file',
        'line',
        'context',
        'is_fatal',
        'occurred_at',
    ];

    protected $casts = [
        'context' => 'array',
        'is_fatal' => 'boolean',
        'occurred_at' => 'datetime',
        'line' => 'integer',
    ];

    public function plugin(): BelongsTo
    {
        return $this->belongsTo(Plugin::class);
    }

    public static function createFromException(\Throwable $exception, Plugin $plugin, bool $isFatal = false): ?self
    {
        if (! Plugin::where('id', $plugin->id)->exists()) {
            return null;
        }

        return static::create([
            'plugin_id' => $plugin->id,
            'error_type' => get_class($exception),
            'error_message' => $exception->getMessage(),
            'stack_trace' => $exception->getTraceAsString(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'context' => [
                'code' => $exception->getCode(),
                'previous' => $exception->getPrevious() ? $exception->getPrevious()->getMessage() : null,
            ],
            'is_fatal' => $isFatal,
            'occurred_at' => now(),
        ]);
    }
}
