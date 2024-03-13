<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $user_id
 * @property string $name
 * @property string $content
 * @property User $creator
 */
class Script extends AbstractModel
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'content',
    ];

    protected $casts = [
        'user_id' => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function executions(): HasMany
    {
        return $this->hasMany(ScriptExecution::class, 'script_id');
    }
}
