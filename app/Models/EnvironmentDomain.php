<?php

namespace App\Models;

use App\Enums\HostedDomainStatus;
use App\Enums\HostedDomainType;
use App\Enums\SslMethod;
use Database\Factories\EnvironmentDomainFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * @property int $environment_id
 * @property int $component_id
 * @property string $host
 * @property HostedDomainType $type
 * @property ?int $redirect_to_domain_id
 * @property SslMethod $ssl_method
 * @property ?int $ssl_id
 * @property bool $force_ssl
 * @property HostedDomainStatus $status
 * @property ?string $error
 * @property Environment $environment
 * @property Component $component
 * @property ?EnvironmentDomain $redirectToDomain
 * @property Collection<int, EnvironmentDomain> $redirects
 * @property ?Ssl $ssl
 */
class EnvironmentDomain extends AbstractModel
{
    /** @use HasFactory<EnvironmentDomainFactory> */
    use HasFactory;

    protected $fillable = ['host', 'type', 'force_ssl'];

    protected $casts = [
        'environment_id' => 'integer',
        'component_id' => 'integer',
        'redirect_to_domain_id' => 'integer',
        'ssl_id' => 'integer',
        'type' => HostedDomainType::class,
        'status' => HostedDomainStatus::class,
        'ssl_method' => SslMethod::class,
        'force_ssl' => 'boolean',
    ];

    /** @return BelongsTo<Environment, covariant $this> */
    public function environment(): BelongsTo
    {
        return $this->belongsTo(Environment::class);
    }

    /** @return BelongsTo<Component, covariant $this> */
    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class);
    }

    /** @return BelongsTo<EnvironmentDomain, covariant $this> */
    public function redirectToDomain(): BelongsTo
    {
        return $this->belongsTo(self::class, 'redirect_to_domain_id');
    }

    /** @return HasMany<EnvironmentDomain, covariant $this> */
    public function redirects(): HasMany
    {
        return $this->hasMany(self::class, 'redirect_to_domain_id');
    }

    /** @return BelongsTo<Ssl, covariant $this> */
    public function ssl(): BelongsTo
    {
        return $this->belongsTo(Ssl::class);
    }
}
