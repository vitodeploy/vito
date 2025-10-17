<?php

namespace App\Models;

use Database\Factories\DNSProviderFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $user_id
 * @property string $name
 * @property string $provider
 * @property array<string, string> $credentials
 * @property bool $connected
 * @property User $user
 * @property ?int $project_id
 * @property Domain[] $domains
 * @property ?Project $project
 */
class DNSProvider extends AbstractModel
{
    /** @use HasFactory<DNSProviderFactory> */
    use HasFactory;

    protected $table = 'dns_providers';

    protected $fillable = [
        'user_id',
        'name',
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
     * @return HasMany<Domain, covariant $this>
     */
    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class, 'dns_provider_id');
    }

    public function provider(): \App\DNSProviders\DNSProvider
    {
        $providerClass = config('dns-provider.providers.'.$this->provider.'.handler');

        /** @var \App\DNSProviders\DNSProvider $provider */
        $provider = new $providerClass($this);

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
     * @return Builder<DNSProvider>
     */
    public static function getByProjectId(int $projectId, User $user): Builder
    {
        /** @var Builder<DNSProvider> $query */
        $query = static::query();

        return $query
            ->where('user_id', $user->id)
            ->where(function (Builder $query) use ($projectId): void {
                $query->where('project_id', $projectId)->orWhereNull('project_id');
            });
    }
}
