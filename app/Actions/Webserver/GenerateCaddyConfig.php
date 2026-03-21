<?php

namespace App\Actions\Webserver;

use App\Enums\LoadBalancerMethod;
use App\Models\HostedDomain;
use App\Models\Site;

class GenerateCaddyConfig extends AbstractGenerateConfig
{
    public function defaultTemplate(): string
    {
        return file_get_contents(resource_path('views/ssh/services/webserver/caddy/vhost.mustache'));
    }

    protected function buildServerBlockKeys(bool $hasSsl, string $sslCertPath, string $sslKeyPath, Site $site): array
    {
        return [
            'http_only' => ! $hasSsl && ! $site->ssl_enabled,
            'has_tls' => $hasSsl,
            'ssl_certificate_path' => $sslCertPath,
            'ssl_certificate_key_path' => $sslKeyPath,
        ];
    }

    protected function buildPhpSocket(Site $site): string
    {
        if ($site->isIsolated()) {
            return "unix//run/php/php{$site->php_version}-fpm-{$site->user}.sock";
        }

        return "unix//var/run/php/php{$site->php_version}-fpm.sock";
    }

    protected function buildLoadBalancerData(Site $site): array
    {
        $data = [
            'lb_servers' => [],
            'lb_policy' => 'round_robin',
        ];

        if ($site->type === 'load-balancer') {
            $method = $site->type_data['method'] ?? LoadBalancerMethod::ROUND_ROBIN->value;
            $data['lb_policy'] = match ($method) {
                LoadBalancerMethod::LEAST_CONNECTIONS->value => 'least_conn',
                LoadBalancerMethod::IP_HASH->value => 'ip_hash',
                default => 'round_robin',
            };

            $data['lb_servers'] = $site->loadBalancerServers->map(fn ($s) => [
                'address' => $s->ip.':'.$s->port,
            ])->all();
        }

        return $data;
    }

    protected function buildRedirectEntry(object $redirect, bool $isProxy): array
    {
        return [
            'id' => $redirect->id,
            'from' => $redirect->from,
            'to' => $redirect->to,
            'mode' => $redirect->mode,
            'is_proxy' => $isProxy,
        ];
    }

    protected function transformDomains(array $domains, bool $httpOnly): array
    {
        $prefix = $httpOnly ? 'http://' : '';

        return array_map(fn (array $domain) => [
            'name' => $domain['name'],
            'address' => $prefix.$domain['name'],
        ], $domains);
    }

    protected function enrichServerBlock(array $block, array $data): array
    {
        $block['lb_servers'] = $data['lb_servers'];
        $block['lb_policy'] = $data['lb_policy'];

        return $block;
    }

    protected function buildRedirectBlock(HostedDomain $hd, string $primaryDomain, Site $site): array
    {
        $hasSsl = $hd->ssl_id && $hd->ssl;
        $redirectScheme = $site->ssl_enabled ? 'https' : 'http';
        $httpOnly = ! $hasSsl && ! $site->ssl_enabled;

        return [
            'http_only' => $httpOnly,
            'has_tls' => (bool) $hasSsl,
            'ssl_certificate_path' => $hasSsl ? $hd->ssl->certificate_path : '',
            'ssl_certificate_key_path' => $hasSsl ? $hd->ssl->pk_path : '',
            'domains' => $this->transformDomains([['name' => $hd->domain]], $httpOnly),
            'redirect_target' => $primaryDomain,
            'redirect_scheme' => $redirectScheme,
        ];
    }
}
