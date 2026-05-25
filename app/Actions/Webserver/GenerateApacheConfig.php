<?php

namespace App\Actions\Webserver;

use App\Enums\LoadBalancerMethod;
use App\Models\HostedDomain;
use App\Models\Site;

class GenerateApacheConfig extends AbstractGenerateConfig
{
    /** @var array<int, string> Collected during server block building */
    private array $forceSSLDomains = [];

    public function defaultTemplate(): string
    {
        return file_get_contents(resource_path('views/ssh/services/webserver/apache/vhost.mustache'));
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
            return "unix:/run/php/php{$site->php_version}-fpm-{$site->user}.sock|fcgi://localhost";
        }

        return "unix:/var/run/php/php{$site->php_version}-fpm.sock|fcgi://localhost";
    }

    protected function buildLoadBalancerData(Site $site): array
    {
        $balancerName = preg_replace('/[^A-Za-z0-9]/', '', $site->domain).'_balancer';
        $isLoadBalancer = $site->type === 'load-balancer';

        $data = [
            'balancer_name' => $balancerName,
            'lb_method' => 'byrequests',
            'lb_servers' => [],
        ];

        if ($isLoadBalancer) {
            $method = $site->type_data['method'] ?? LoadBalancerMethod::ROUND_ROBIN->value;
            $data['lb_method'] = match ($method) {
                LoadBalancerMethod::LEAST_CONNECTIONS->value => 'bybusyness',
                default => 'byrequests',
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
            'from' => $redirect->from,
            'to' => $redirect->to,
            'mode' => $redirect->mode,
            'is_proxy' => $isProxy,
        ];
    }

    protected function transformDomains(array $domains, bool $httpOnly): array
    {
        return $domains;
    }

    protected function enrichServerBlock(array $block, array $data): array
    {
        $names = array_map(fn (array $domain) => $domain['name'], $block['domains']);

        $block['server_name'] = $names[0] ?? $data['primary_domain'];
        $block['server_aliases'] = array_map(fn (string $name) => ['name' => $name], array_slice($names, 1));
        $block['balancer_name'] = $data['balancer_name'];
        $block['lb_method'] = $data['lb_method'];
        $block['lb_servers'] = $data['lb_servers'];

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
            'server_name' => $hd->domain,
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
                        $this->forceSSLDomains[] = $domain['name'];
                    }
                }
            }
        }

        $this->forceSSLDomains = array_values(array_unique($this->forceSSLDomains));

        $data['vhosts'] = $this->expandVhosts($data['server_blocks']);
        $data['redirect_vhosts'] = $this->expandVhosts($data['redirect_blocks']);

        $data['has_force_ssl_redirect'] = ! empty($this->forceSSLDomains);
        $data['force_ssl_server_name'] = $this->forceSSLDomains[0] ?? '';
        $data['force_ssl_aliases'] = array_map(fn (string $name) => ['name' => $name], array_slice($this->forceSSLDomains, 1));
        $data['force_ssl_root'] = $site->getWebDirectoryPath();

        return $data;
    }

    protected function formatConfig(string $config): string
    {
        $lines = explode("\n", trim($config));
        $formatted = [];
        $lastWasEmpty = false;

        foreach ($lines as $line) {
            $line = rtrim($line);
            $isEmpty = trim($line) === '';

            if ($isEmpty && $lastWasEmpty) {
                continue;
            }

            $formatted[] = $line;
            $lastWasEmpty = $isEmpty;
        }

        return implode("\n", $formatted)."\n";
    }

    /**
     * Expand brace-style server blocks into one entry per listen port.
     *
     * @param  array<int, array<string, mixed>>  $blocks
     * @return array<int, array<string, mixed>>
     */
    private function expandVhosts(array $blocks): array
    {
        $vhosts = [];

        foreach ($blocks as $block) {
            if ($block['listen_80'] ?? false) {
                $vhosts[] = ['listen_port' => 80, 'has_ssl' => false] + $block;
            }

            if ($block['listen_443'] ?? false) {
                $vhosts[] = ['listen_port' => 443, 'has_ssl' => true] + $block;
            }
        }

        return $vhosts;
    }
}
