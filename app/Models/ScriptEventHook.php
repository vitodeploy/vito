<?php

namespace App\Models;

use App\Enums\ScriptEventHookEvent;
use Carbon\Carbon;
use Database\Factories\ScriptEventHookFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $script_id
 * @property int $user_id
 * @property int $project_id
 * @property int $server_id
 * @property ScriptEventHookEvent $event
 * @property string $user
 * @property bool $enabled
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Script $script
 * @property User $creator
 * @property Project $project
 * @property Server $server
 */
class ScriptEventHook extends AbstractModel
{
    /** @use HasFactory<ScriptEventHookFactory> */
    use HasFactory;

    protected $fillable = [
        'script_id',
        'user_id',
        'project_id',
        'server_id',
        'event',
        'user',
        'enabled',
    ];

    protected $casts = [
        'script_id' => 'integer',
        'user_id' => 'integer',
        'project_id' => 'integer',
        'server_id' => 'integer',
        'event' => ScriptEventHookEvent::class,
        'enabled' => 'boolean',
    ];

    /**
     * @return BelongsTo<Script, covariant $this>
     */
    public function script(): BelongsTo
    {
        return $this->belongsTo(Script::class);
    }

    /**
     * @return BelongsTo<User, covariant $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<Project, covariant $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<Server, covariant $this>
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }
}
