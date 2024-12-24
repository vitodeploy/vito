<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

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

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Project, $this>
     */
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

    /**
     * @return HasMany<ScriptExecution, $this>
     */
    public function executions(): HasMany
    {
        return $this->hasMany(ScriptExecution::class);
    }

    /**
     * @return HasOne<ScriptExecution, $this>
     */
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
