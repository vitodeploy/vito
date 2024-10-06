<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $content
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Collection<ScriptExecution> $executions
 * @property ?ScriptExecution $lastExecution
 * @property User $user
 * @property int $project_id
 * @property ?Project $project
 */
class Script extends AbstractModel
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'content',
        'project_id',
    ];

    protected $casts = [
        'user_id' => 'int',
        'project_id' => 'int',
    ];

    public static function boot(): void
    {
        parent::boot();

        static::deleting(function (Script $script) {
            $script->executions()->delete();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function getVariables(): array
    {
        $variables = [];
        preg_match_all('/\${(.*?)}/', $this->content, $matches);
        foreach ($matches[1] as $match) {
            $variables[] = $match;
        }

        return array_unique($variables);
    }

    public function executions(): HasMany
    {
        return $this->hasMany(ScriptExecution::class);
    }

    public function lastExecution(): HasOne
    {
        return $this->hasOne(ScriptExecution::class)->latest();
    }

    public static function getByProjectId(int $projectId, int $userId): Builder
    {
        return self::query()
            ->where(function (Builder $query) use ($projectId, $userId) {
                $query->where('project_id', $projectId)
                    ->orWhere('user_id', $userId)
                    ->orWhereNull('project_id');
            });
    }
}
