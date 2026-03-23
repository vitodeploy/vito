<?php

namespace App\Models;

use App\Enums\SslStatus;
use App\Enums\SslType;
use Carbon\Carbon;
use Database\Factories\SslFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    /**
     * @return HasMany<HostedDomain, covariant $this>
     */
    public function hostedDomains(): HasMany
    {
        return $this->hasMany(HostedDomain::class);
    }

    /**
     * @return Builder<Ssl>
     */
    public static function activeServerLevel(int $serverId): Builder
    {
        return self::query()
            ->whereNull('site_id')
            ->where('server_id', $serverId)
            ->where('status', SslStatus::CREATED)
            ->whereNot('type', SslType::CSR)
            ->whereNotNull('certificate_path')
            ->whereNotNull('pk_path');
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
     * Check if a wildcard domain pattern matches the given domain.
     *
     * *.example.com matches sub.example.com
     * *.example.com does NOT match example.com (bare domain)
     * *.example.com does NOT match a.b.example.com (nested subdomain)
     */
    public static function wildcardMatches(string $pattern, string $domain): bool
    {
        if (! str_starts_with($pattern, '*.')) {
            return false;
        }

        $parent = substr($pattern, 2);
        $suffix = '.'.$parent;

        if (! str_ends_with(strtolower($domain), strtolower($suffix))) {
            return false;
        }

        $prefix = substr($domain, 0, -strlen($suffix));

        return $prefix !== '' && ! str_contains($prefix, '.');
    }

    /**
     * Check if this SSL certificate covers the given domain.
     */
    public function coversDomain(string $domain): bool
    {
        foreach ($this->getDomains() as $sslDomain) {
            if (strtolower($sslDomain) === strtolower($domain)) {
                return true;
            }
            if (static::wildcardMatches($sslDomain, $domain)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return BelongsTo<ServerLog, covariant $this>
     */
    public function log(): BelongsTo
    {
        return $this->belongsTo(ServerLog::class);
    }
}
