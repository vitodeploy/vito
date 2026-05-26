<?php

namespace App\Actions\SiteStats;

use App\Models\Site;
use Exception;

class RenderSiteStatsConf
{
    public function render(Site $site): string
    {
        $domain = $this->safeDomain($site);
        $caddy = $site->webserver()::id() === 'caddy';

        $vars = [
            'SITE_ID' => (string) $site->id,
            'DOMAIN' => $domain,
            'LOG_FORMAT' => $caddy ? 'CADDY' : 'COMBINED',
            'LIVE_LOG' => $caddy ? "/var/log/caddy/{$domain}-access.log" : "/var/log/nginx/{$domain}-access.log",
            'LOG_GLOB' => $caddy ? "/var/log/caddy/{$domain}-access*.log*" : "/var/log/nginx/{$domain}-access.log*",
            'RETENTION_MONTHS' => (string) (int) ($site->server->service('log_analysis')?->type_data['data_retention'] ?? 12),
            'SSH_USER' => $site->server->getSshUser(),
        ];

        $lines = [];
        foreach ($vars as $key => $value) {
            $lines[] = $key."='".$value."'";
        }

        return implode("\n", $lines)."\n";
    }

    private function safeDomain(Site $site): string
    {
        $domain = (string) $site->domain;

        if (! preg_match('/^[A-Za-z0-9.\-]+$/', $domain)) {
            throw new Exception('Unsafe site domain for stats processing.');
        }

        return $domain;
    }
}
