<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\TagFactory;
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
    /** @use HasFactory<TagFactory> */
    use HasFactory;

    protected $fillable = [
        'project_id',
        'name',
        'color',
    ];

    protected $casts = [
        'project_id' => 'int',
    ];

    /**
     * @return BelongsTo<Project, covariant $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return MorphToMany<Server, covariant $this>
     */
    public function servers(): MorphToMany
    {
        return $this->morphedByMany(Server::class, 'taggable');
    }

    /**
     * @return MorphToMany<Site, covariant $this>
     */
    public function sites(): MorphToMany
    {
        return $this->morphedByMany(Site::class, 'taggable');
    }

    /**
     * @return Builder<Tag>
     */
    public static function getByProjectId(int $projectId): Builder
    {
        /** @var Builder<Tag> $query */
        $query = static::query();

        return $query
            ->where(function (Builder $query) use ($projectId): void {
                $query->where('project_id', $projectId)->orWhereNull('project_id');
            });
    }
}
