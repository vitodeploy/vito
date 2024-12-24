<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

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

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return MorphToMany<Server, $this>
     */
    public function servers(): MorphToMany
    {
        return $this->morphedByMany(Server::class, 'taggable');
    }

    /**
     * @return MorphToMany<Site, $this>
     */
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
