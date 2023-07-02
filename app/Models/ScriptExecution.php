<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $script_id
 * @property int $server_id
 * @property string $user
 * @property Carbon $finished_at
 * @property ?Server $server
 * @property Script $script
 */
class ScriptExecution extends AbstractModel
{
    use HasFactory;

    protected $fillable = [
        'script_id',
        'server_id',
        'user',
        'finished_at',
    ];

    protected $casts = [
        'script_id' => 'integer',
        'server_id' => 'integer',
    ];

    public function script(): BelongsTo
    {
        return $this->belongsTo(Script::class);
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }
}
