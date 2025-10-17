<?php

namespace App\Models;

use Database\Factories\DNSRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $domain_id
 * @property string $type
 * @property string $name
 * @property string $content
 * @property int $ttl
 * @property bool $proxied
 * @property string $provider_record_id
 * @property array<string, mixed> $metadata
 * @property Domain $domain
 */
class DNSRecord extends AbstractModel
{
    /** @use HasFactory<DNSRecordFactory> */
    use HasFactory;

    protected $table = 'dns_records';

    protected $fillable = [
        'domain_id',
        'type',
        'name',
        'content',
        'ttl',
        'proxied',
        'provider_record_id',
        'metadata',
    ];

    protected $casts = [
        'domain_id' => 'integer',
        'ttl' => 'integer',
        'proxied' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * @return BelongsTo<Domain, covariant $this>
     */
    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    /**
     * Get the formatted name for display
     */
    public function getFormattedNameAttribute(): string
    {
        return $this->name === $this->domain->domain ? '@' : $this->name;
    }

    /**
     * Get the TTL in human readable format
     */
    public function getFormattedTtlAttribute(): string
    {
        if ($this->ttl === 1) {
            return 'Auto';
        }

        if ($this->ttl < 60) {
            return $this->ttl.'s';
        }

        if ($this->ttl < 3600) {
            return round($this->ttl / 60).'m';
        }

        return round($this->ttl / 3600).'h';
    }
}
