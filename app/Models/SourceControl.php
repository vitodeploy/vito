<?php

namespace App\Models;

use App\SourceControlProviders\SourceControlProvider;
use Database\Factories\SourceControlFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $provider
 * @property array<string, string> $provider_data
 * @property string $profile
 * @property ?string $url
 * @property string $access_token
 * @property ?int $project_id
 */
class SourceControl extends AbstractModel
{
    /** @use HasFactory<SourceControlFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'provider',
        'provider_data',
        'profile',
        'url',
        'access_token',
        'project_id',
    ];

    protected $casts = [
        'access_token' => 'encrypted',
        'provider_data' => 'encrypted:array',
        'project_id' => 'integer',
    ];

    public function provider(): SourceControlProvider
    {
        $providerClass = config('source-control.providers.'.$this->provider.'.handler');

        /** @var SourceControlProvider $provider */
        $provider = new $providerClass($this);

        return $provider;
    }

    public function getRepo(string $repo): mixed
    {
        return $this->provider()->getRepo($repo);
    }

    /**
     * @return HasMany<Site, covariant $this>
     */
    public function sites(): HasMany
    {
        return $this->hasMany(Site::class);
    }

    /**
     * @return BelongsTo<Project, covariant $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return Builder<SourceControl>
     */
    public static function getByProjectId(int $projectId): Builder
    {
        /** @var Builder<SourceControl> $query */
        $query = static::query();

        return $query
            ->where(function (Builder $query) use ($projectId): void {
                $query->where('project_id', $projectId)->orWhereNull('project_id');
            });
    }
}
