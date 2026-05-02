<?php

namespace App\Models;

use Database\Factories\DomainFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * @property int $dns_provider_id
 * @property int $user_id
 * @property int $project_id
 * @property string $domain
 * @property string $provider_domain_id
 * @property array<string, mixed> $metadata
 * @property DNSProvider $dnsProvider
 * @property User $user
 * @property Project $project
 * @property DNSRecord[] $records
 */
class Domain extends AbstractModel
{
    /** @use HasFactory<DomainFactory> */
    use HasFactory;

    protected $fillable = [
        'dns_provider_id',
        'user_id',
        'project_id',
        'domain',
        'provider_domain_id',
        'metadata',
    ];

    protected $casts = [
        'dns_provider_id' => 'integer',
        'user_id' => 'integer',
        'project_id' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * @return BelongsTo<DNSProvider, covariant $this>
     */
    public function dnsProvider(): BelongsTo
    {
        return $this->belongsTo(DNSProvider::class);
    }

    /**
     * @return BelongsTo<User, covariant $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Project, covariant $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return HasMany<DNSRecord, covariant $this>
     */
    public function records(): HasMany
    {
        return $this->hasMany(DNSRecord::class);
    }

    /**
     * @throws \Throwable
     */
    public function syncDnsRecords(): void
    {
        $records = $this->dnsProvider->provider()->getRecords($this->provider_domain_id);

        DB::transaction(function () use ($records) {
            DNSRecord::where('domain_id', $this->id)->delete();

            foreach ($records as $recordData) {
                DNSRecord::create([
                    'domain_id' => $this->id,
                    'type' => $recordData['type'],
                    'name' => $recordData['name'],
                    'content' => $recordData['content'],
                    'ttl' => $recordData['ttl'] ?? 1,
                    'proxied' => $recordData['proxied'] ?? false,
                    'priority' => $recordData['priority'] ?? null,
                    'provider_record_id' => $recordData['id'],
                    'metadata' => $recordData,
                ]);
            }
        });
    }
}
