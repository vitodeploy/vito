<?php

namespace App\Models;

use App\SourceControlProviders\SourceControlProvider;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $provider
 * @property array $provider_data
 * @property ?string $profile
 * @property ?string $url
 * @property string $access_token
 * @property ?int $project_id
 */
class SourceControl extends AbstractModel
{
    use HasFactory;

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
        $providerClass = config('core.source_control_providers_class')[$this->provider];

        return new $providerClass($this);
    }

    public function getRepo(?string $repo = null): ?array
    {
        return $this->provider()->getRepo($repo);
    }

    public function sites(): HasMany
    {
        return $this->hasMany(Site::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public static function getByProjectId(int $projectId): Builder
    {
        return self::query()
            ->where('project_id', $projectId)
            ->orWhereNull('project_id');
    }
}
