<?php

namespace App\Actions\Site;

use App\Enums\HostedDomainStatus;
use App\Enums\HostedDomainType;
use App\Enums\LoadBalancerMethod;
use App\Models\HostedDomain;
use App\Models\Site;
use App\Models\Ssl;
use Illuminate\Support\Collection;
use Mustache_Engine;

class GenerateNginxConfig
{
    /**
     * Generate the full nginx vhost config for a site.
     */
    public function generate(Site $site): string
    {
        $site->load(['hostedDomains.ssl', 'activeRedirects', 'loadBalancerServers']);

        $template = $this->getTemplate($site);
        $data = $this->buildData($site);

        $engine = new Mustache_Engine;

        return format_nginx_config($engine->render($template, $data));
    }

    /**
     * Get the default Mustache template contents.
     */
    public function defaultTemplate(): string
    {
        return file_get_contents(resource_path('views/ssh/services/webserver/nginx/vhost.mustache'));
    }

    protected function getTemplate(Site $site): string
    {
        if ($site->vhost_template) {
            return $site->vhost_template;
        }

        return $this->defaultTemplate();
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildData(Site $site): array
    {
        $activeHostedDomains = $site->hostedDomains
            ->filter(fn (HostedDomain $hd) => $hd->status === HostedDomainStatus::ACTIVE);

        $hasHostedDomains = $activeHostedDomains->isNotEmpty();

        if ($hasHostedDomains) {
            return $this->buildFromHostedDomains($site, $activeHostedDomains);
        }

        return $this->buildFromLegacy($site);
    }

    /**
     * Build data from HostedDomains (new system).
     *
     * @param  Collection<int, HostedDomain>  $activeDomains
     * @return array<string, mixed>
     */
    protected function buildFromHostedDomains(Site $site, Collection $activeDomains): array
    {
        $primaryAndAlias = $activeDomains->filter(
            fn (HostedDomain $hd) => in_array($hd->type, [HostedDomainType::PRIMARY, HostedDomainType::ALIAS])
        );
        $redirectDomains = $activeDomains->filter(
            fn (HostedDomain $hd) => $hd->type === HostedDomainType::REDIRECT
        );

        $primaryDomain = $site->domain;
        $data = $this->buildCommonData($site, $primaryDomain);

        $serverBlocks = [];
        $forceSSLDomains = [];

        if ($site->ssl_enabled) {
            $grouped = $primaryAndAlias->groupBy(fn (HostedDomain $hd) => $hd->ssl_id ?? 'http');

            foreach ($grouped as $sslId => $domains) {
                $domainNames = $domains->map(fn (HostedDomain $hd) => ['name' => $hd->domain])->values()->all();

                if ($sslId === 'http') {
                    $serverBlocks[] = [
                        'listen_80' => true,
                        'listen_443' => false,
                        'ssl_certificate_path' => '',
                        'ssl_certificate_key_path' => '',
                        'domains' => $domainNames,
                    ];
                } else {
                    /** @var Ssl $ssl */
                    $ssl = $domains->first()->ssl;
                    $serverBlocks[] = [
                        'listen_80' => ! $site->force_ssl,
                        'listen_443' => true,
                        'ssl_certificate_path' => $ssl->certificate_path,
                        'ssl_certificate_key_path' => $ssl->pk_path,
                        'domains' => $domainNames,
                    ];

                    if ($site->force_ssl) {
                        foreach ($domainNames as $dn) {
                            $forceSSLDomains[] = $dn;
                        }
                    }
                }
            }
        } else {
            $allDomainNames = $primaryAndAlias->map(fn (HostedDomain $hd) => ['name' => $hd->domain])->values()->all();
            if (! empty($allDomainNames)) {
                $serverBlocks[] = [
                    'listen_80' => true,
                    'listen_443' => false,
                    'ssl_certificate_path' => '',
                    'ssl_certificate_key_path' => '',
                    'domains' => $allDomainNames,
                ];
            }
        }

        $data['server_blocks'] = $this->enrichServerBlocks($serverBlocks, $data);
        $data['has_force_ssl_redirect'] = ! empty($forceSSLDomains);
        $data['force_ssl_domains'] = $forceSSLDomains;

        $data['redirect_blocks'] = $this->buildRedirectBlocks($redirectDomains, $primaryDomain, $site);

        return $data;
    }

    /**
     * Build data from legacy site fields (no HostedDomains).
     *
     * @return array<string, mixed>
     */
    protected function buildFromLegacy(Site $site): array
    {
        $primaryDomain = $site->domain;
        $data = $this->buildCommonData($site, $primaryDomain);

        $allDomains = collect([['name' => $site->domain]]);
        foreach ($site->aliases ?? [] as $alias) {
            $allDomains->push(['name' => $alias]);
        }
        $domainNames = $allDomains->all();

        $activeSsl = $site->activeSsl;

        $serverBlocks = [];
        $forceSSLDomains = [];

        if ($activeSsl && $site->ssl_enabled) {
            $serverBlocks[] = [
                'listen_80' => ! $site->force_ssl,
                'listen_443' => true,
                'ssl_certificate_path' => $activeSsl->certificate_path,
                'ssl_certificate_key_path' => $activeSsl->pk_path,
                'domains' => $domainNames,
            ];

            if ($site->force_ssl) {
                $forceSSLDomains = $domainNames;
            }
        } else {
            $serverBlocks[] = [
                'listen_80' => true,
                'listen_443' => false,
                'ssl_certificate_path' => '',
                'ssl_certificate_key_path' => '',
                'domains' => $domainNames,
            ];
        }

        $data['server_blocks'] = $this->enrichServerBlocks($serverBlocks, $data);
        $data['has_force_ssl_redirect'] = ! empty($forceSSLDomains);
        $data['force_ssl_domains'] = $forceSSLDomains;
        $data['redirect_blocks'] = [];

        return $data;
    }

    /**
     * Build data common to both hosted-domain and legacy paths.
     *
     * @return array<string, mixed>
     */
    protected function buildCommonData(Site $site, string $primaryDomain): array
    {
        $siteType = $site->type;
        $isPhp = in_array($siteType, ['php', 'php-blank', 'laravel', 'wordpress', 'phpmyadmin']);
        $isReverseProxy = in_array($siteType, ['nodejs', 'mise_nodejs', 'mise_bun']);
        $isLoadBalancer = $siteType === 'load-balancer';
        $isOctane = (bool) data_get($site->type_data, 'octane', false);

        $phpSocket = '';
        if ($isPhp) {
            $phpSocket = "unix:/var/run/php/php{$site->php_version}-fpm.sock";
            if ($site->isIsolated()) {
                $phpSocket = "unix:/run/php/php{$site->php_version}-fpm-{$site->user}.sock";
            }
        }

        $backendName = preg_replace('/[^A-Za-z0-9 ]/', '', $site->domain).'_backend';

        $data = [
            'primary_domain' => $primaryDomain,
            'root' => $site->getWebDirectoryPath(),
            'is_php' => $isPhp && ! $isOctane,
            'is_reverse_proxy' => $isReverseProxy,
            'is_load_balancer' => $isLoadBalancer,
            'is_octane' => $isOctane,
            'has_octane_map' => $isOctane,
            'octane_port' => data_get($site->type_data, 'octane_port', 8000),
            'php_socket' => $phpSocket,
            'port' => $site->port,

            'has_upstream' => $isLoadBalancer,
            'upstream_name' => $backendName,
            'upstream_method_least_conn' => false,
            'upstream_method_ip_hash' => false,
            'upstream_servers' => [],

            'redirects' => $this->buildRedirects($site),
        ];

        if ($isLoadBalancer) {
            $method = $site->type_data['method'] ?? LoadBalancerMethod::ROUND_ROBIN->value;
            $data['upstream_method_least_conn'] = $method === LoadBalancerMethod::LEAST_CONNECTIONS->value;
            $data['upstream_method_ip_hash'] = $method === LoadBalancerMethod::IP_HASH->value;

            $servers = $site->loadBalancerServers->map(fn ($s) => [
                'ip' => $s->ip,
                'port' => $s->port,
                'backup' => $s->backup,
                'has_weight' => (bool) $s->weight,
                'weight' => $s->weight,
            ])->all();

            $data['upstream_servers'] = $servers;
        }

        return $data;
    }

    /**
     * Add shared data fields to each server block.
     *
     * @param  array<int, array<string, mixed>>  $blocks
     * @param  array<string, mixed>  $data
     * @return array<int, array<string, mixed>>
     */
    protected function enrichServerBlocks(array $blocks, array $data): array
    {
        foreach ($blocks as &$block) {
            $block['root'] = $data['root'];
            $block['primary_domain'] = $data['primary_domain'];
            $block['is_php'] = $data['is_php'];
            $block['is_reverse_proxy'] = $data['is_reverse_proxy'];
            $block['is_load_balancer'] = $data['is_load_balancer'];
            $block['is_octane'] = $data['is_octane'];
            $block['octane_port'] = $data['octane_port'];
            $block['php_socket'] = $data['php_socket'];
            $block['port'] = $data['port'];
            $block['upstream_name'] = $data['upstream_name'];
            $block['redirects'] = $data['redirects'];
        }

        return $blocks;
    }

    /**
     * Build URL redirect data for Mustache.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function buildRedirects(Site $site): array
    {
        $redirects = [];
        foreach ($site->activeRedirects as $redirect) {
            $isProxy = $redirect->mode === 1000;
            $redirects[] = [
                'from' => $redirect->from,
                'to' => $redirect->to,
                'mode' => $redirect->mode,
                'is_proxy' => $isProxy,
                'to_host' => $isProxy ? parse_url($redirect->to, PHP_URL_HOST) : '',
            ];
        }

        return $redirects;
    }

    /**
     * Build redirect-type HostedDomain server blocks.
     *
     * @param  Collection<int, HostedDomain>  $redirectDomains
     * @return array<int, array<string, mixed>>
     */
    protected function buildRedirectBlocks(Collection $redirectDomains, string $primaryDomain, Site $site): array
    {
        $blocks = [];
        foreach ($redirectDomains as $hd) {
            $hasSsl = $hd->ssl_id && $hd->ssl;
            $redirectScheme = $site->ssl_enabled ? 'https' : 'http';

            $blocks[] = [
                'listen_80' => true,
                'listen_443' => (bool) $hasSsl,
                'ssl_certificate_path' => $hasSsl ? $hd->ssl->certificate_path : '',
                'ssl_certificate_key_path' => $hasSsl ? $hd->ssl->pk_path : '',
                'domains' => [['name' => $hd->domain]],
                'redirect_target' => $primaryDomain,
                'redirect_scheme' => $redirectScheme,
            ];
        }

        return $blocks;
    }
}
