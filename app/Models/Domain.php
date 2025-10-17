<?php

namespace App\Models;

use Database\Factories\DomainFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * @property int $dns_provider_id
 * @property int $user_id
 * @property string $domain
 * @property string $provider_domain_id
 * @property array<string, mixed> $metadata
 * @property DNSProvider $dnsProvider
 * @property User $user
 * @property DNSRecord[] $records
 */
class Domain extends AbstractModel
{
    /** @use HasFactory<DomainFactory> */
    use HasFactory;

    protected $fillable = [
        'dns_provider_id',
        'user_id',
        'domain',
        'provider_domain_id',
        'metadata',
    ];

    protected $casts = [
        'dns_provider_id' => 'integer',
        'user_id' => 'integer',
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
     * @return HasMany<DNSRecord, covariant $this>
     */
    public function records(): HasMany
    {
        return $this->hasMany(DNSRecord::class);
    }

    /**
     * @return Builder<Domain>
     */
    public static function getByProjectId(int $projectId, User $user): Builder
    {
        /** @var Builder<Domain> $query */
        $query = static::query();

        return $query
            ->where('user_id', $user->id)
            ->whereHas('dnsProvider', function (Builder $query) use ($projectId): void {
                $query->where(function (Builder $query) use ($projectId): void {
                    $query->where('project_id', $projectId)->orWhereNull('project_id');
                });
            });
    }

    public function syncDnsRecords(): void
    {
        try {
            $records = $this->dnsProvider->provider()->getRecords($this->provider_domain_id);

            DNSRecord::where('domain_id', $this->id)->delete();

            foreach ($records as $recordData) {
                DNSRecord::create([
                    'domain_id' => $this->id,
                    'type' => $recordData['type'],
                    'name' => $recordData['name'],
                    'content' => $recordData['content'],
                    'ttl' => $recordData['ttl'] ?? 1,
                    'proxied' => $recordData['proxied'] ?? false,
                    'provider_record_id' => $recordData['id'],
                    'metadata' => $recordData,
                ]);
            }
        } catch (Throwable $e) {
            Log::error('Failed to sync DNS records for domain: '.$this->domain, [
                'error' => $e->getMessage(),
                'domain_id' => $this->id,
            ]);
        }
    }
}
