<?php

namespace App\Actions\Webserver;

use App\Enums\LoadBalancerMethod;
use App\Models\HostedDomain;
use App\Models\Site;

class GenerateNginxConfig extends AbstractGenerateConfig
{
    /** @var array<int, array<string, string>> Collected during server block building */
    private array $forceSSLDomains = [];

    public function defaultTemplate(): string
    {
        return file_get_contents(resource_path('views/ssh/services/webserver/nginx/vhost.mustache'));
    }

    protected function buildServerBlockKeys(bool $hasSsl, string $sslCertPath, string $sslKeyPath, Site $site): array
    {
        return [
            'listen_80' => $hasSsl ? ! $site->force_ssl : true,
            'listen_443' => $hasSsl,
            'ssl_certificate_path' => $sslCertPath,
            'ssl_certificate_key_path' => $sslKeyPath,
        ];
    }

    protected function buildPhpSocket(Site $site): string
    {
        if ($site->isIsolated()) {
            return "unix:/run/php/php{$site->php_version}-fpm-{$site->user}.sock";
        }

        return "unix:/var/run/php/php{$site->php_version}-fpm.sock";
    }

    protected function buildLoadBalancerData(Site $site): array
    {
        $backendName = preg_replace('/[^A-Za-z0-9 ]/', '', $site->domain).'_backend';
        $isLoadBalancer = $site->type === 'load-balancer';

        $data = [
            'has_upstream' => $isLoadBalancer,
            'upstream_name' => $backendName,
            'upstream_method_least_conn' => false,
            'upstream_method_ip_hash' => false,
            'upstream_servers' => [],
        ];

        if ($isLoadBalancer) {
            $method = $site->type_data['method'] ?? LoadBalancerMethod::ROUND_ROBIN->value;
            $data['upstream_method_least_conn'] = $method === LoadBalancerMethod::LEAST_CONNECTIONS->value;
            $data['upstream_method_ip_hash'] = $method === LoadBalancerMethod::IP_HASH->value;

            $data['upstream_servers'] = $site->loadBalancerServers->map(fn ($s) => [
                'ip' => $s->ip,
                'port' => $s->port,
                'backup' => $s->backup,
                'has_weight' => (bool) $s->weight,
                'weight' => $s->weight,
            ])->all();
        }

        return $data;
    }

    protected function buildRedirectEntry(object $redirect, bool $isProxy): array
    {
        return [
            'from' => $redirect->from,
            'to' => $redirect->to,
            'mode' => $redirect->mode,
            'is_proxy' => $isProxy,
            'to_host' => $isProxy ? parse_url($redirect->to, PHP_URL_HOST) : '',
        ];
    }

    protected function transformDomains(array $domains, bool $httpOnly): array
    {
        return $domains;
    }

    protected function enrichServerBlock(array $block, array $data): array
    {
        $block['upstream_name'] = $data['upstream_name'];

        return $block;
    }

    protected function buildRedirectBlock(HostedDomain $hd, string $primaryDomain, Site $site): array
    {
        $hasSsl = $hd->ssl_id && $hd->ssl;
        $redirectScheme = $site->ssl_enabled ? 'https' : 'http';

        return [
            'listen_80' => true,
            'listen_443' => (bool) $hasSsl,
            'ssl_certificate_path' => $hasSsl ? $hd->ssl->certificate_path : '',
            'ssl_certificate_key_path' => $hasSsl ? $hd->ssl->pk_path : '',
            'domains' => [['name' => $hd->domain]],
            'redirect_target' => $primaryDomain,
            'redirect_scheme' => $redirectScheme,
        ];
    }

    protected function buildData(Site $site): array
    {
        $this->forceSSLDomains = [];

        return parent::buildData($site);
    }

    protected function finalizeData(array $data, Site $site): array
    {
        if ($site->force_ssl && $site->ssl_enabled) {
            foreach ($data['server_blocks'] as $block) {
                if ($block['listen_443'] ?? false) {
                    foreach ($block['domains'] as $domain) {
                        $this->forceSSLDomains[] = $domain;
                    }
                }
            }
        }

        $isOctane = (bool) data_get($site->type_data, 'octane', false);
        $data['has_octane_map'] = $isOctane;
        $data['has_force_ssl_redirect'] = ! empty($this->forceSSLDomains);
        $data['force_ssl_domains'] = $this->forceSSLDomains;

        return $data;
    }
}
