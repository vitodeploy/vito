<?php

namespace App\Actions\Site;

use App\Enums\HostedDomainStatus;
use App\Enums\SslStatus;
use App\Models\HostedDomain;
use App\Models\Site;
use Illuminate\Support\Collection;

class GetSiteWarnings
{
    /**
     * Get warnings for a single site.
     *
     * @return array<int, array{key: string, ...}>
     */
    public function forSite(Site $site): array
    {
        $warnings = [];

        $pendingDomains = $site->hostedDomains()
            ->where('status', HostedDomainStatus::PENDING)
            ->pluck('domain')
            ->all();

        if (count($pendingDomains) > 0) {
            $warnings[] = [
                'key' => 'pending_domains',
                'count' => count($pendingDomains),
                'domains' => $pendingDomains,
            ];
        }

        if (! $site->ssl_enabled) {
            $warnings[] = [
                'key' => 'ssl_disabled',
            ];
        }

        if (! $site->vhost_generation_enabled) {
            $warnings[] = [
                'key' => 'vhost_generation_disabled',
            ];
        }

        $expiringSsls = $site->hostedDomains()
            ->whereNotNull('ssl_id')
            ->whereHas('ssl', fn ($q) => $q->where('status', SslStatus::CREATED)->where('expires_at', '<=', now()->addDays(14)))
            ->with('ssl')
            ->get();

        if ($expiringSsls->isNotEmpty()) {
            $warnings[] = [
                'key' => 'ssl_expiring',
                'count' => $expiringSsls->count(),
                'domains' => $expiringSsls->pluck('domain')->all(),
                'earliest_expiry' => $expiringSsls->min(fn (HostedDomain $hd) => $hd->ssl->expires_at)->toIso8601String(),
            ];
        }

        return $warnings;
    }

    /**
     * Get warnings for multiple sites (batch-optimized).
     *
     * @param  Collection<int, Site>  $sites
     * @return array<int, array<int, array{key: string, ...}>> keyed by site ID
     */
    public function forSites(Collection $sites): array
    {
        $siteIds = $sites->pluck('id')->all();

        $pendingBysite = HostedDomain::query()
            ->whereIn('site_id', $siteIds)
            ->where('status', HostedDomainStatus::PENDING)
            ->get(['site_id', 'domain'])
            ->groupBy('site_id');

        $expiringBySite = HostedDomain::query()
            ->whereIn('site_id', $siteIds)
            ->whereNotNull('ssl_id')
            ->whereHas('ssl', fn ($q) => $q->where('status', SslStatus::CREATED)->where('expires_at', '<=', now()->addDays(14)))
            ->with('ssl')
            ->get()
            ->groupBy('site_id');

        $warnings = [];

        foreach ($sites as $site) {
            $siteWarnings = [];

            $pending = $pendingBysite->get($site->id);
            if ($pending && $pending->isNotEmpty()) {
                $domains = $pending->pluck('domain')->all();
                $siteWarnings[] = [
                    'key' => 'pending_domains',
                    'count' => count($domains),
                    'domains' => $domains,
                ];
            }

            if (! $site->ssl_enabled) {
                $siteWarnings[] = [
                    'key' => 'ssl_disabled',
                ];
            }

            if (! $site->vhost_generation_enabled) {
                $siteWarnings[] = [
                    'key' => 'vhost_generation_disabled',
                ];
            }

            $expiring = $expiringBySite->get($site->id);
            if ($expiring && $expiring->isNotEmpty()) {
                $siteWarnings[] = [
                    'key' => 'ssl_expiring',
                    'count' => $expiring->count(),
                    'domains' => $expiring->pluck('domain')->all(),
                    'earliest_expiry' => $expiring->min(fn (HostedDomain $hd) => $hd->ssl->expires_at)->toIso8601String(),
                ];
            }

            $warnings[$site->id] = $siteWarnings;
        }

        return $warnings;
    }
}
