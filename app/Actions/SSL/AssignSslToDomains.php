<?php

namespace App\Actions\SSL;

use App\Actions\Site\BroadcastSiteUpdate;
use App\Models\HostedDomain;
use App\Models\Site;
use App\Models\Ssl;
use Illuminate\Support\Collection;

class AssignSslToDomains
{
    /**
     * @return Collection<int, HostedDomain>
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

        if ($changed->isNotEmpty()) {
            app(BroadcastSiteUpdate::class)->broadcast($site);
        }

        return $changed;
    }

    /**
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
