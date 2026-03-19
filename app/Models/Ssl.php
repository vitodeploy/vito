<?php

namespace App\Models;

use App\Enums\SslStatus;
use Carbon\Carbon;
use Database\Factories\SslFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property ?int $site_id
 * @property ?int $server_id
 * @property ?int $domain_id
 * @property string $type
 * @property ?string $certificate
 * @property ?string $pk
 * @property ?string $ca
 * @property ?array $csr_data
 * @property ?string $csr_passphrase
 * @property ?Carbon $expires_at
 * @property SslStatus $status
 * @property ?Site $site
 * @property ?Server $server
 * @property ?Domain $domain
 * @property array<int, string>|string|null $domains
 * @property int $log_id
 * @property string $email
 * @property bool $is_active
 * @property bool $is_wildcard
 * @property bool $has_csr
 * @property ?string $certificate_path
 * @property ?string $pk_path
 * @property ?string $ca_path
 * @property ?ServerLog $log
 */
class Ssl extends AbstractModel
{
    /** @use HasFactory<SslFactory> */
    use HasFactory;

    protected $fillable = [
        'site_id',
        'server_id',
        'domain_id',
        'type',
        'certificate',
        'pk',
        'ca',
        'csr_data',
        'csr_passphrase',
        'expires_at',
        'status',
        'domains',
        'log_id',
        'email',
        'is_active',
        'is_wildcard',
        'has_csr',
        'certificate_path',
        'pk_path',
        'ca_path',
    ];

    protected $casts = [
        'site_id' => 'integer',
        'server_id' => 'integer',
        'domain_id' => 'integer',
        'certificate' => 'encrypted',
        'pk' => 'encrypted',
        'ca' => 'encrypted',
        'csr_data' => 'array',
        'csr_passphrase' => 'encrypted',
        'expires_at' => 'datetime',
        'domains' => 'array',
        'log_id' => 'integer',
        'is_active' => 'boolean',
        'is_wildcard' => 'boolean',
        'has_csr' => 'boolean',
        'status' => SslStatus::class,
    ];

    /**
     * @return BelongsTo<Site, covariant $this>
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * @return BelongsTo<Server, covariant $this>
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * @return BelongsTo<Domain, covariant $this>
     */
    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function validateSetup(string $result): bool
    {
        if (! Str::contains($result, 'Successfully received certificate')) {
            return false;
        }

        if ($this->type == 'letsencrypt') {
            $this->certificate_path = '/etc/letsencrypt/live/'.$this->id.'/fullchain.pem';
            $this->pk_path = '/etc/letsencrypt/live/'.$this->id.'/privkey.pem';
            $this->save();
        }

        return true;
    }

    /**
     * @return array<string>
     */
    public function getDomains(): array
    {
        if (! empty($this->domains) && is_array($this->domains)) {
            return $this->domains;
        }

        $this->domains = [$this->site->domain];
        $this->save();

        return $this->domains;
    }

    /**
     * @return BelongsTo<ServerLog, covariant $this>
     */
    public function log(): BelongsTo
    {
        return $this->belongsTo(ServerLog::class);
    }
}
