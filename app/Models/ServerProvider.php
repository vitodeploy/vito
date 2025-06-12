<?php

namespace App\Models;

use Database\Factories\ServerProviderFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

/**
 * @property int $user_id
 * @property string $profile
 * @property string $provider
 * @property array<string, string> $credentials
 * @property bool $connected
 * @property User $user
 * @property ?int $project_id
 * @property Server[] $servers
 * @property ?Project $project
 */
class ServerProvider extends AbstractModel
{
    /** @use HasFactory<ServerProviderFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'profile',
        'provider',
        'credentials',
        'connected',
        'project_id',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'credentials' => 'encrypted:array',
        'connected' => 'boolean',
        'project_id' => 'integer',
    ];

    /**
     * @return BelongsTo<User, covariant $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    public function getCredentials(): array
    {
        return $this->credentials;
    }

    /**
     * @return HasMany<Server, covariant $this>
     */
    public function servers(): HasMany
    {
        return $this->hasMany(Server::class, 'provider_id');
    }

    public function provider(): \App\ServerProviders\ServerProvider
    {
        $providerClass = config('server-provider.providers.'.$this->provider.'.handler');

        /** @var \App\ServerProviders\ServerProvider $provider */
        $provider = new $providerClass($this, new Server);

        return $provider;
    }

    /**
     * @return BelongsTo<Project, covariant $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return Builder<ServerProvider>
     */
    public static function getByProjectId(int $projectId): Builder
    {
        /** @var Builder<ServerProvider> $query */
        $query = static::query();

        return $query
            ->where(function (Builder $query) use ($projectId): void {
                $query->where('project_id', $projectId)->orWhereNull('project_id');
            });
    }

    /**
     * @return array<string>
     */
    public static function regions(?int $id): array
    {
        if ($id === null || $id === 0) {
            return [];
        }
        /** @var ?ServerProvider $profile */
        $profile = self::find($id);
        if (! $profile) {
            return [];
        }

        if (Cache::get('regions-'.$id)) {
            return Cache::get('regions-'.$id);
        }

        $regions = $profile->provider()->regions();
        Cache::put('regions-'.$id, $regions, 600);

        return $regions;
    }

    /**
     * @return array<string>
     */
    public static function plans(?int $id, ?string $region): array
    {
        if ($id === null || $id === 0) {
            return [];
        }
        $profile = self::find($id);
        if (! $profile) {
            return [];
        }

        return $profile->provider()->plans($region);
    }
}
