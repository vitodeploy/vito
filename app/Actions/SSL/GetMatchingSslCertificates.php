<?php

namespace App\Actions\SSL;

use App\Models\Site;
use App\Models\Ssl;
use Illuminate\Support\Collection;

class GetMatchingSslCertificates
{
    /**
     * Get all server-level SSL certificates that match any of the site's hosted domains.
     *
     * @return Collection<int, array{id: int, label: string, domains: array<int, string>}>
     */
    public function get(Site $site): Collection
    {
        $siteDomains = $site->hostedDomains()->pluck('domain')->all();

        return $this->filterSsls($site, $siteDomains);
    }

    /**
     * Get all server-level SSL certificates that match a specific domain.
     *
     * @return Collection<int, array{id: int, label: string, domains: array<int, string>}>
     */
    public function forDomain(Site $site, string $domain): Collection
    {
        return $this->filterSsls($site, [$domain]);
    }

    /**
     * @param  array<int, string>  $domains
     * @return Collection<int, array{id: int, label: string, domains: array<int, string>}>
     */
    private function filterSsls(Site $site, array $domains): Collection
    {
        $serverSsls = Ssl::activeServerLevel($site->server_id)
            ->get();

        return $serverSsls
            ->filter(function (Ssl $ssl) use ($domains): bool {
                foreach ($ssl->domains ?? [] as $sslDomain) {
                    foreach ($domains as $domain) {
                        if (strcasecmp($sslDomain, $domain) === 0) {
                            return true;
                        }
                        if (Ssl::wildcardMatches($sslDomain, $domain)) {
                            return true;
                        }
                    }
                }

                return false;
            })
            ->map(fn (Ssl $ssl) => [
                'id' => $ssl->id,
                'label' => $ssl->type.' #'.$ssl->id.' ('.implode(', ', (array) ($ssl->domains ?? [])).')',
                'domains' => (array) ($ssl->domains ?? []),
            ])
            ->values();
    }
}
