<?php

namespace App\Actions\Component;

use App\Enums\ComponentStatus;
use App\Enums\HostedDomainStatus;
use App\Enums\HostedDomainType;
use App\Enums\SslMethod;
use App\Enums\SslStatus;
use App\Models\Component;
use App\Models\Environment;
use App\Models\EnvironmentDomain;
use App\Services\Webserver\AbstractWebserver;
use Mustache\Engine;
use RuntimeException;

class SyncComponentVhost
{
    public function sync(Component $component): void
    {
        $service = $component->server->webserver();
        if (! $service || ! in_array($service->name, ['nginx', 'caddy'], true)) {
            throw new RuntimeException('The component needs a supported webserver.');
        }
        /** @var AbstractWebserver $webserver */
        $webserver = $service->handler();
        $webserver->deploySplash();
        $content = $this->generate($component);
        $path = '/etc/'.$service->name.'/sites-available/component-'.$component->id;
        if ($content !== '') {
            $component->server->ssh()->write($path.'.pending', $content, 'root');
        }
        $component->server->ssh()->exec(view('ssh.components.sync-vhost', [
            'name' => $service->name, 'file' => 'component-'.$component->id, 'remove' => $content === '',
        ]), 'sync-component-vhost');
    }

    public function verificationKey(Environment $environment): string
    {
        return hash_hmac('sha256', 'environment:'.$environment->id, (string) config('app.key'));
    }

    public function generate(Component $component): string
    {
        $component->load('capabilities', 'environment.application');
        if (! $component->capabilities->contains(fn ($capability): bool => $capability->capability === 'web' && $capability->status !== ComponentStatus::DELETING)) {
            return '';
        }
        $name = $component->server->webserver()?->name;
        $domains = $component->domains()->where('status', HostedDomainStatus::ACTIVE)->with('ssl', 'component', 'redirectToDomain.ssl', 'redirectToDomain.component')->orderBy('id')->get();
        $data = $component->environment->application->preset()->vhostData($component);
        $data['php_socket'] = ($name === 'caddy' ? 'unix/' : 'unix:').$data['php_socket'];
        $data += [
            'is_component' => true,
            'primary_domain' => 'component-'.$component->id,
            'verification_key' => $this->verificationKey($component->environment),
            'server_blocks' => [], 'redirect_blocks' => [], 'force_ssl_domains' => [], 'http_verification_domains' => [],
        ];
        foreach ($domains as $domain) {
            if ($domain->type === HostedDomainType::REDIRECT && $domain->redirectToDomain?->status !== HostedDomainStatus::ACTIVE) {
                continue;
            }
            $certificate = $this->hasCertificate($domain);
            $automatic = $name === 'caddy' && $domain->ssl_method === SslMethod::LETSENCRYPT && $domain->ssl_id === null && ! str_starts_with($domain->host, '*.');
            $tls = $certificate || $automatic;
            $force = $domain->force_ssl && $tls;
            $block = [
                'domains' => [['name' => $domain->host, 'address' => ($tls ? 'https://' : 'http://').$domain->host]],
                'listen_80' => ! $force,
                'listen_443' => $certificate,
                'has_tls' => $certificate,
                'ssl_certificate_path' => $certificate ? $domain->ssl->certificate_path : '',
                'ssl_certificate_key_path' => $certificate ? $domain->ssl->pk_path : '',
            ];
            if ($tls && ! $force && $name === 'caddy') {
                $block['domains'][] = ['name' => $domain->host, 'address' => 'http://'.$domain->host];
            }
            if ($force) {
                $data['force_ssl_domains'][] = ['name' => $domain->host];
                $data['http_verification_domains'][] = ['address' => 'http://'.$domain->host];
            }
            if ($domain->type === HostedDomainType::REDIRECT) {
                $target = $domain->redirectToDomain;
                $targetTls = $this->hasCertificate($target) || ($name === 'caddy' && $target->ssl_method === SslMethod::LETSENCRYPT && $target->ssl_id === null);
                $block['redirect_target'] = $target->host;
                $block['redirect_scheme'] = $target->force_ssl && $targetTls ? 'https' : 'http';
                $data['redirect_blocks'][] = $block;
            } else {
                $data['server_blocks'][] = $block;
            }
        }
        if ($data['server_blocks'] === [] && $data['redirect_blocks'] === []) {
            return '';
        }
        $data['has_force_ssl_redirect'] = $data['force_ssl_domains'] !== [];
        $data['has_http_verification'] = $data['http_verification_domains'] !== [];
        $template = file_get_contents(resource_path('views/ssh/services/webserver/'.$name.'/vhost.mustache'));

        return format_webserver_config((new Engine(['escape' => fn ($value) => $value]))->render($template, $data));
    }

    public function hasCertificate(EnvironmentDomain $domain): bool
    {
        $ssl = $domain->ssl;

        return $domain->ssl_method !== SslMethod::NONE && $ssl !== null && $ssl->status === SslStatus::CREATED
            && $ssl->site_id === null && $ssl->server_id === $domain->component->server_id
            && $ssl->environment_id === ($domain->ssl_method === SslMethod::CUSTOM ? null : $domain->environment_id)
            && $ssl->certificate_path && $ssl->pk_path && $ssl->expires_at?->isFuture()
            && is_array($ssl->domains) && $ssl->domains !== [] && $ssl->coversDomain($domain->host);
    }
}
