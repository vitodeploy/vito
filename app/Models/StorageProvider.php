<?php

namespace App\Models;

use Database\Factories\StorageProviderFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $user_id
 * @property string $profile
 * @property string $provider
 * @property array<string, string> $credentials
 * @property User $user
 * @property ?int $project_id
 */
class StorageProvider extends AbstractModel
{
    /** @use HasFactory<StorageProviderFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'profile',
        'provider',
        'credentials',
        'project_id',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'credentials' => 'encrypted:array',
        'project_id' => 'integer',
    ];

    /**
     * @return BelongsTo<User, covariant $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function provider(): \App\StorageProviders\StorageProvider
    {
        $providerClass = config('storage-provider.providers.'.$this->provider.'.handler');

        /** @var \App\StorageProviders\StorageProvider $provider */
        $provider = new $providerClass($this, new Server);

        return $provider;
    }

    /**
     * @return HasMany<Backup, covariant $this>
     */
    public function backups(): HasMany
    {
        return $this->hasMany(Backup::class, 'storage_id');
    }

    /**
     * @return BelongsTo<Project, covariant $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return Builder<StorageProvider>
     */
    public static function getByProjectId(int $projectId): Builder
    {
        /** @var Builder<StorageProvider> $query */
        $query = static::query();

        return $query
            ->where(function (Builder $query) use ($projectId): void {
                $query->where('project_id', $projectId)->orWhereNull('project_id');
            });
    }
}
