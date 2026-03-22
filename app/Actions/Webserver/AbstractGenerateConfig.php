<?php

namespace App\Actions\Webserver;

use App\Enums\HostedDomainStatus;
use App\Enums\HostedDomainType;
use App\Models\HostedDomain;
use App\Models\Site;
use App\Models\Ssl;
use Illuminate\Support\Collection;
use Mustache\Engine;

abstract class AbstractGenerateConfig
{
    /**
     * Generate the full vhost config for a site.
     */
    public function generate(Site $site, ?string $template = null): string
    {
        $site->load(['hostedDomains.ssl', 'activeRedirects', 'loadBalancerServers']);

        $template = $template ?? $this->getTemplate($site);
        $data = $this->buildData($site);

        $engine = new Engine([
            'escape' => fn ($value) => $value,
        ]);

        return format_webserver_config($engine->render($template, $data));
    }

    /**
     * Get the default Mustache template contents.
     */
    abstract public function defaultTemplate(): string;

    /**
     * Build server block keys for a given SSL/HTTP state.
     *
     * @return array<string, mixed>
     */
    abstract protected function buildServerBlockKeys(bool $hasSsl, string $sslCertPath, string $sslKeyPath, Site $site): array;

    /**
     * Build the PHP socket path for the given site.
     */
    abstract protected function buildPhpSocket(Site $site): string;

    /**
     * Build load balancer data for the template context.
     *
     * @return array<string, mixed>
     */
    abstract protected function buildLoadBalancerData(Site $site): array;

    /**
     * Build a single redirect entry for Mustache.
     *
     * @return array<string, mixed>
     */
    abstract protected function buildRedirectEntry(object $redirect, bool $isProxy): array;

    /**
     * Enrich server block domains (e.g. Caddy adds http:// prefix).
     *
     * @param  array<int, array<string, string>>  $domains
     * @return array<int, array<string, string>>
     */
    abstract protected function transformDomains(array $domains, bool $httpOnly): array;

    /**
     * Add webserver-specific keys to each server block.
     *
     * @param  array<string, mixed>  $block
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    abstract protected function enrichServerBlock(array $block, array $data): array;

    /**
     * Build a redirect-type HostedDomain server block.
     *
     * @return array<string, mixed>
     */
    abstract protected function buildRedirectBlock(HostedDomain $hd, string $primaryDomain, Site $site): array;

    /**
     * Add webserver-specific data after server blocks are built (e.g. force_ssl for Nginx).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function finalizeData(array $data, Site $site): array
    {
        return $data;
    }

    protected function getTemplate(Site $site): string
    {
        if ($site->vhost_template) {
            return $site->vhost_template;
        }

        $siteTypeTemplate = $site->type()->vhostTemplate($site->server->webserver()->name);
        if ($siteTypeTemplate !== null) {
            return $siteTypeTemplate;
        }

        return $this->defaultTemplate();
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildData(Site $site): array
    {
        $primary = $site->hostedDomains
            ->first(fn (HostedDomain $hd) => $hd->type === HostedDomainType::PRIMARY);

        $activeOthers = $site->hostedDomains
            ->filter(fn (HostedDomain $hd) => $hd->type !== HostedDomainType::PRIMARY && $hd->status === HostedDomainStatus::ACTIVE);

        $domains = $activeOthers;
        if ($primary) {
            $domains = $domains->prepend($primary);
        }

        return $this->buildFromHostedDomains($site, $domains);
    }

    /**
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

        if ($site->ssl_enabled) {
            $grouped = $primaryAndAlias->groupBy(fn (HostedDomain $hd) => $hd->ssl_id ?? 'http');

            foreach ($grouped as $sslId => $domains) {
                $domainNames = $domains->map(fn (HostedDomain $hd) => ['name' => $hd->domain])->values()->all();

                if ($sslId === 'http') {
                    $serverBlocks[] = [
                        ...$this->buildServerBlockKeys(false, '', '', $site),
                        'domains' => $domainNames,
                    ];
                } else {
                    /** @var Ssl $ssl */
                    $ssl = $domains->first()->ssl;
                    $serverBlocks[] = [
                        ...$this->buildServerBlockKeys(true, $ssl->certificate_path, $ssl->pk_path, $site),
                        'domains' => $domainNames,
                    ];
                }
            }
        } else {
            $allDomainNames = $primaryAndAlias->map(fn (HostedDomain $hd) => ['name' => $hd->domain])->values()->all();
            if (! empty($allDomainNames)) {
                $serverBlocks[] = [
                    ...$this->buildServerBlockKeys(false, '', '', $site),
                    'domains' => $allDomainNames,
                ];
            }
        }

        $data['server_blocks'] = $this->enrichServerBlocks($serverBlocks, $data);

        $data['redirect_blocks'] = [];
        foreach ($redirectDomains as $hd) {
            $data['redirect_blocks'][] = $this->buildRedirectBlock($hd, $primaryDomain, $site);
        }

        return $this->finalizeData($data, $site);
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildCommonData(Site $site, string $primaryDomain): array
    {
        $siteTypeData = $site->type()->vhostData();
        $isOctane = (bool) data_get($site->type_data, 'octane', false);
        $isPhp = ($siteTypeData['is_php'] ?? false) && ! $isOctane;

        return [
            ...$siteTypeData,
            ...$this->buildLoadBalancerData($site),
            'primary_domain' => $primaryDomain,
            'root' => $site->getWebDirectoryPath(),
            'is_php' => $isPhp,
            'is_reverse_proxy' => $siteTypeData['is_reverse_proxy'] ?? false,
            'is_load_balancer' => $siteTypeData['is_load_balancer'] ?? false,
            'is_octane' => $isOctane,
            'octane_port' => data_get($site->type_data, 'octane_port', 8000),
            'php_socket' => $isPhp ? $this->buildPhpSocket($site) : '',
            'port' => $site->port,
            'redirects' => $this->buildRedirects($site),
            'type_data' => $site->type_data ?? [],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $blocks
     * @param  array<string, mixed>  $data
     * @return array<int, array<string, mixed>>
     */
    protected function enrichServerBlocks(array $blocks, array $data): array
    {
        foreach ($blocks as &$block) {
            $httpOnly = $block['http_only'] ?? (! ($block['listen_443'] ?? false));
            $block['domains'] = $this->transformDomains($block['domains'], $httpOnly);
            $block['root'] = $data['root'];
            $block['primary_domain'] = $data['primary_domain'];
            $block['is_php'] = $data['is_php'];
            $block['is_reverse_proxy'] = $data['is_reverse_proxy'];
            $block['is_load_balancer'] = $data['is_load_balancer'];
            $block['is_octane'] = $data['is_octane'];
            $block['octane_port'] = $data['octane_port'];
            $block['php_socket'] = $data['php_socket'];
            $block['port'] = $data['port'];
            $block['redirects'] = $data['redirects'];
            $block = $this->enrichServerBlock($block, $data);
        }

        return $blocks;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function buildRedirects(Site $site): array
    {
        $redirects = [];
        foreach ($site->activeRedirects as $redirect) {
            $isProxy = (int) $redirect->mode === 1000;
            $redirects[] = $this->buildRedirectEntry($redirect, $isProxy);
        }

        return $redirects;
    }
}
