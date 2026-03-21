<?php

namespace App\Actions\Site;

use App\Enums\HostedDomainStatus;
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

        if (! $site->vhost_generation_enabled) {
            $warnings[] = [
                'key' => 'vhost_generation_disabled',
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

            if (! $site->vhost_generation_enabled) {
                $siteWarnings[] = [
                    'key' => 'vhost_generation_disabled',
                ];
            }

            $warnings[$site->id] = $siteWarnings;
        }

        return $warnings;
    }
}
