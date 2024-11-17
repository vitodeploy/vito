<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

/**
 * @property int $user_id
 * @property string $profile
 * @property string $provider
 * @property array $credentials
 * @property bool $connected
 * @property User $user
 * @property ?int $project_id
 * @property Server[] $servers
 * @property Project $project
 */
class ServerProvider extends AbstractModel
{
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getCredentials(): array
    {
        return $this->credentials;
    }

    public function servers(): HasMany
    {
        return $this->hasMany(Server::class, 'provider_id');
    }

    public function provider(): \App\ServerProviders\ServerProvider
    {
        $providerClass = config('core.server_providers_class')[$this->provider];

        return new $providerClass($this);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public static function getByProjectId(int $projectId): Builder
    {
        return self::query()
            ->where(function (Builder $query) use ($projectId) {
                $query->where('project_id', $projectId)->orWhereNull('project_id');
            });
    }

    public static function regions(?int $id): array
    {
        if (! $id) {
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

    public static function plans(?int $id, ?string $region): array
    {
        if (! $id) {
            return [];
        }
        $profile = self::find($id);
        if (! $profile) {
            return [];
        }

        return $profile->provider()->plans($region);
    }
}
