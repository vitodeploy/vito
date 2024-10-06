<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * @property int $id
 * @property int $project_id
 * @property string $name
 * @property string $color
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Tag extends AbstractModel
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'name',
        'color',
    ];

    protected $casts = [
        'project_id' => 'int',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function servers(): MorphToMany
    {
        return $this->morphedByMany(Server::class, 'taggable');
    }

    public function sites(): MorphToMany
    {
        return $this->morphedByMany(Site::class, 'taggable');
    }

    public static function getByProjectId(int $projectId): Builder
    {
        return self::query()
            ->where(function (Builder $query) use ($projectId) {
                $query->where('project_id', $projectId)->orWhereNull('project_id');
            });
    }
}
