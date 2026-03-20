<?php

namespace App\Actions\SSL;

use App\Enums\SslStatus;
use App\Models\Site;
use App\Models\Ssl;
use Illuminate\Support\Collection;

class GetMatchingSslCertificates
{
    /**
     * Get all server-level SSL certificates that match any of the site's hosted domains.
     *
     * @return Collection<int, array{id: int, label: string, domains: array<string>}>
     */
    public function handle(Site $site): Collection
    {
        $siteDomains = $site->hostedDomains()->pluck('domain')->all();

        return $this->filterSsls($site, $siteDomains);
    }

    /**
     * Get all server-level SSL certificates that match a specific domain.
     *
     * @return Collection<int, array{id: int, label: string, domains: array<string>}>
     */
    public function forDomain(Site $site, string $domain): Collection
    {
        return $this->filterSsls($site, [$domain]);
    }

    /**
     * @param  array<string>  $domains
     * @return Collection<int, array{id: int, label: string, domains: array<string>}>
     */
    private function filterSsls(Site $site, array $domains): Collection
    {
        $serverSsls = Ssl::query()
            ->whereNull('site_id')
            ->where('server_id', $site->server_id)
            ->where('status', SslStatus::CREATED)
            ->where('is_active', true)
            ->get();

        return $serverSsls
            ->filter(function (Ssl $ssl) use ($domains): bool {
                foreach ($ssl->domains ?? [] as $sslDomain) {
                    foreach ($domains as $domain) {
                        if (strcasecmp($sslDomain, $domain) === 0) {
                            return true;
                        }
                        if ($this->wildcardMatches($sslDomain, $domain)) {
                            return true;
                        }
                    }
                }

                return false;
            })
            ->map(fn (Ssl $ssl) => [
                'id' => $ssl->id,
                'label' => $ssl->type.' #'.$ssl->id.' ('.implode(', ', $ssl->domains ?? []).')',
                'domains' => $ssl->domains ?? [],
            ])
            ->values();
    }

    private function wildcardMatches(string $pattern, string $domain): bool
    {
        if (! str_starts_with($pattern, '*.')) {
            return false;
        }

        $parent = substr($pattern, 2);
        $suffix = '.'.$parent;

        if (! str_ends_with(strtolower($domain), strtolower($suffix))) {
            return false;
        }

        $prefix = substr($domain, 0, -strlen($suffix));

        return $prefix !== '' && ! str_contains($prefix, '.');
    }
}
