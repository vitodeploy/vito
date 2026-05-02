<?php

namespace App\Actions\Domain;

use App\Models\DNSProvider;
use App\Models\Domain;
use Illuminate\Support\Facades\Cache;

class GetAvailableDomains
{
    /**
     * Get available domains from the DNS provider that haven't been added yet.
     *
     * @return array<int, array<string, mixed>>
     */
    public function execute(DNSProvider $dnsProvider, bool $useCache = true): array
    {
        $existing = Domain::where('dns_provider_id', $dnsProvider->id)
            ->pluck('provider_domain_id')
            ->toArray();

        $cacheKey = "dns_provider_{$dnsProvider->id}_domains";

        $domains = $useCache ? Cache::get($cacheKey) : null;

        if ($domains === null) {
            $domains = $dnsProvider->provider()->getDomains();
            Cache::put($cacheKey, $domains, 3600);
        }

        return array_values(array_filter($domains, fn (array $domain) => ! in_array($domain['id'], $existing)));
    }
}
