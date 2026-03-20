<?php

namespace App\Actions\SSL;

use App\Enums\SslStatus;
use App\Models\HostedDomain;
use App\Models\Site;
use App\Models\Ssl;
use Illuminate\Support\Collection;

class AssignSslToDomains
{
    /**
     * Assign the best-matching server-level SSL to each hosted domain.
     *
     * @return Collection<int, HostedDomain> Only domains where ssl_id changed
     */
    public function assign(Site $site, ?Collection $hostedDomains = null): Collection
    {
        $hostedDomains = $hostedDomains ?? $site->hostedDomains()->get();

        $serverSsls = Ssl::query()
            ->whereNull('site_id')
            ->where('server_id', $site->server_id)
            ->where('status', SslStatus::CREATED)
            ->where('is_active', true)
            ->get();

        $changed = collect();

        foreach ($hostedDomains as $hostedDomain) {
            $bestSsl = $this->findBestMatch($hostedDomain->domain, $serverSsls);
            $newSslId = $bestSsl?->id;

            if ($hostedDomain->ssl_id !== $newSslId) {
                $hostedDomain->ssl_id = $newSslId;
                $hostedDomain->save();
                $changed->push($hostedDomain);
            }
        }

        return $changed;
    }

    /**
     * Find the best matching SSL for a domain.
     * Priority: exact match > wildcard match.
     *
     * @param  Collection<int, Ssl>  $ssls
     */
    public function findBestMatch(string $domain, Collection $ssls): ?Ssl
    {
        $exactMatch = null;
        $wildcardMatch = null;

        foreach ($ssls as $ssl) {
            $sslDomains = $ssl->domains ?? [];

            foreach ($sslDomains as $sslDomain) {
                if (strcasecmp($sslDomain, $domain) === 0) {
                    $exactMatch = $ssl;
                    break 2;
                }

                if ($this->wildcardMatches($sslDomain, $domain) && $wildcardMatch === null) {
                    $wildcardMatch = $ssl;
                }
            }
        }

        return $exactMatch ?? $wildcardMatch;
    }

    /**
     * Check if a wildcard domain pattern matches the given domain.
     *
     * *.example.com matches sub.example.com
     * *.example.com does NOT match example.com (bare domain)
     * *.example.com does NOT match a.b.example.com (nested subdomain)
     */
    private function wildcardMatches(string $pattern, string $domain): bool
    {
        if (! str_starts_with($pattern, '*.')) {
            return false;
        }

        $parent = substr($pattern, 2); // Remove "*."
        $suffix = '.'.$parent;

        if (! str_ends_with(strtolower($domain), strtolower($suffix))) {
            return false;
        }

        // Ensure there's exactly one label before the parent
        // e.g., "sub.example.com" has 1 label before "example.com"
        $prefix = substr($domain, 0, -strlen($suffix));

        return $prefix !== '' && ! str_contains($prefix, '.');
    }
}
