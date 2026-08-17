<?php

namespace App\Models;

use App\SourceControlProviders\SourceControlProvider;
use App\Traits\HasProjectScopedQueries;
use Database\Factories\SourceControlFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\Rule;

/**
 * @property string $provider
 * @property array<string, string> $provider_data
 * @property string $profile
 * @property ?string $url
 * @property string $access_token
 * @property ?int $project_id
 * @property int $user_id
 * @property ?string $external_identifier
 * @property ?Project $project
 * @property User $user
 */
class SourceControl extends AbstractModel
{
    public const string PROVIDER_GITHUB_APP = 'github-app';

    /** @use HasFactory<SourceControlFactory> */
    use HasFactory;

    use HasProjectScopedQueries;
    use SoftDeletes;

    protected $fillable = [
        'provider',
        'provider_data',
        'profile',
        'url',
        'access_token',
        'project_id',
        'user_id',
        'external_identifier',
    ];

    protected $casts = [
        'access_token' => 'encrypted',
        'provider_data' => 'encrypted:array',
        'project_id' => 'integer',
        'user_id' => 'integer',
    ];

    public function isGithubApp(): bool
    {
        return $this->provider === self::PROVIDER_GITHUB_APP;
    }

    /**
     * @return array<int, mixed>
     */
    public static function siteValidationRules(Server $server): array
    {
        /** @var array<string, array<string, mixed>> $providers */
        $providers = config('source-control.providers', []);

        $usableProviders = array_keys(array_filter(
            $providers,
            fn (array $config): bool => (bool) ($config['usable_for_sites'] ?? true),
        ));

        return [
            'required',
            Rule::exists('source_controls', 'id')
                ->whereIn('provider', $usableProviders)
                ->where(function ($query) use ($server): void {
                    $query->where('user_id', $server->user_id)
                        ->where(function ($q) use ($server): void {
                            $q->where('project_id', $server->project_id)->orWhereNull('project_id');
                        });
                }),
        ];
    }

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
     * @return BelongsTo<User, covariant $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
