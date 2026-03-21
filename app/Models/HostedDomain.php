<?php

namespace App\Models;

use App\Enums\HostedDomainStatus;
use App\Enums\HostedDomainType;
use App\Enums\SslMethod;
use Database\Factories\HostedDomainFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

/**
 * @property int $site_id
 * @property string $domain
 * @property HostedDomainType $type
 * @property HostedDomainStatus $status
 * @property SslMethod $ssl_method
 * @property ?int $ssl_id
 * @property ?string $error
 * @property ?Site $site
 * @property ?Ssl $ssl
 */
class HostedDomain extends AbstractModel
{
    /** @use HasFactory<HostedDomainFactory> */
    use HasFactory;

    protected $fillable = [
        'site_id',
        'domain',
        'type',
        'status',
        'ssl_method',
        'ssl_id',
        'error',
    ];

    protected $casts = [
        'site_id' => 'integer',
        'ssl_id' => 'integer',
        'type' => HostedDomainType::class,
        'status' => HostedDomainStatus::class,
        'ssl_method' => SslMethod::class,
    ];

    /**
     * @throws ValidationException
     */
    public function ensureModifiable(string $action): void
    {
        if ($this->type === HostedDomainType::PRIMARY) {
            throw ValidationException::withMessages([
                'domain' => ["Cannot {$action} the primary domain."],
            ]);
        }

        if ($this->status->isProcessing()) {
            throw ValidationException::withMessages([
                'domain' => ["Cannot {$action} a domain while it is {$this->status->value}."],
            ]);
        }
    }

    /**
     * @return BelongsTo<Site, covariant $this>
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * @return BelongsTo<Ssl, covariant $this>
     */
    public function ssl(): BelongsTo
    {
        return $this->belongsTo(Ssl::class);
    }
}
