<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\ScriptFactory;
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
 * @property Collection<int, ScriptExecution> $executions
 * @property ?ScriptExecution $lastExecution
 * @property User $user
 * @property ?int $project_id
 * @property ?Project $project
 */
class Script extends AbstractModel
{
    /** @use HasFactory<ScriptFactory> */
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

        static::deleting(function (Script $script): void {
            $script->executions()->delete();
        });
    }

    /**
     * @return BelongsTo<User, covariant $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Project, covariant $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return array<string>
     */
    public function getVariables(): array
    {
        $variables = [];
        preg_match_all('/\${(.*?)}/', $this->content, $matches);
        $variables = $matches[1];

        return array_unique($variables);
    }

    /**
     * @return HasMany<ScriptExecution, covariant $this>
     */
    public function executions(): HasMany
    {
        return $this->hasMany(ScriptExecution::class);
    }

    /**
     * @return HasOne<ScriptExecution, covariant $this>
     */
    public function lastExecution(): HasOne
    {
        return $this->hasOne(ScriptExecution::class)->latest();
    }

    /**
     * @return Builder<Script>
     */
    public static function getByProjectId(int $projectId, int $userId): Builder
    {
        /** @var Builder<Script> $query */
        $query = static::query();

        return $query
            ->where(function (Builder $query) use ($projectId, $userId): void {
                $query->where('project_id', $projectId)
                    ->orWhere('user_id', $userId)
                    ->orWhereNull('project_id');
            });
    }
}
