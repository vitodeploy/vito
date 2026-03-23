<?php

namespace App\Actions\SSL;

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

        $serverSsls = Ssl::activeServerLevel($site->server_id)
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

                if (Ssl::wildcardMatches($sslDomain, $domain) && $wildcardMatch === null) {
                    $wildcardMatch = $ssl;
                }
            }
        }

        return $exactMatch ?? $wildcardMatch;
    }
}
