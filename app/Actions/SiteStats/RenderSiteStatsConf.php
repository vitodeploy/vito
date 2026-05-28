<?php

namespace App\Actions\SiteStats;

use App\Models\Site;
use Exception;

class RenderSiteStatsConf
{
    /**
     * @throws Exception
     */
    public function render(Site $site, ?string $webserverId = null, ?int $retentionMonths = null): string
    {
        $domain = $this->safeDomain($site);
        $caddy = ($webserverId ?? $this->resolveWebserverId($site)) === 'caddy';
        $retention = $retentionMonths ?? (int) ($site->server->service('log_analysis')?->type_data['data_retention'] ?? 12);

        $vars = [
            'SITE_ID' => (string) $site->id,
            'DOMAIN' => $domain,
            'LOG_FORMAT' => $caddy ? 'CADDY' : 'COMBINED',
            'LIVE_LOG' => $caddy ? "/var/log/caddy/{$domain}-access.log" : "/var/log/nginx/{$domain}-access.log",
            'LOG_GLOB' => $caddy ? "/var/log/caddy/{$domain}-access*.log*" : "/var/log/nginx/{$domain}-access.log*",
            'RETENTION_MONTHS' => (string) $retention,
            'SSH_USER' => $this->safeSshUser($site),
        ];

        $lines = [];
        foreach ($vars as $key => $value) {
            $lines[] = $key."='".$value."'";
        }

        return implode("\n", $lines)."\n";
    }

    private function resolveWebserverId(Site $site): string
    {
        $webserver = $site->server->webserver();

        return $webserver ? $webserver->handler()::id() : 'nginx';
    }

    private function safeDomain(Site $site): string
    {
        $domain = (string) $site->domain;

        if (! preg_match('/^[A-Za-z0-9.\-]+$/', $domain)) {
            throw new Exception('Unsafe site domain for stats processing.');
        }

        return $domain;
    }

    private function safeSshUser(Site $site): string
    {
        $user = (string) $site->server->getSshUser();

        if (! preg_match('/^[A-Za-z0-9_.\-]+$/', $user)) {
            throw new Exception('Unsafe SSH user for stats processing.');
        }

        return $user;
    }
}
