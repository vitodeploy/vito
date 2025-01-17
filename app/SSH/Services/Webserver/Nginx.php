<?php

namespace App\SSH\Services\Webserver;

use App\Exceptions\SSHError;
use App\Exceptions\SSLCreationException;
use App\Models\Site;
use App\Models\Ssl;
use App\SSH\HasScripts;
use Closure;
use Illuminate\Support\Str;
use Throwable;

class Nginx extends AbstractWebserver
{
    use HasScripts;

    public function install(): void
    {
        $this->service->server->ssh()->exec(
            $this->getScript('nginx/install-nginx.sh', [
                'config' => $this->getScript('nginx/nginx.conf', [
                    'user' => $this->service->server->getSshUser(),
                ]),
            ]),
            'install-nginx'
        );
        $this->service->server->os()->cleanup();
    }

    public function deletionRules(): array
    {
        return [
            'service' => [
                function (string $attribute, mixed $value, Closure $fail) {
                    $hasSite = $this->service->server->sites()
                        ->exists();
                    if ($hasSite) {
                        $fail('Cannot uninstall webserver while you have websites using it.');
                    }
                },
            ],
        ];
    }

    public function uninstall(): void
    {
        $this->service->server->ssh()->exec(
            $this->getScript('nginx/uninstall-nginx.sh'),
            'uninstall-nginx'
        );
        $this->service->server->os()->cleanup();
    }

    /**
     * @throws SSHError
     */
    public function createVHost(Site $site): void
    {
        // We need to get the isolated user first, if the site is isolated
        // otherwise, use the default ssh user
        $ssh = $this->service->server->ssh($site->user);

        $ssh->exec(
            $this->getScript('nginx/create-path.sh', [
                'path' => $site->path,
            ]),
            'create-path',
            $site->id
        );

        $this->service->server->ssh()->exec(
            $this->getScript('nginx/create-vhost.sh', [
                'domain' => $site->domain,
                'path' => $site->path,
                'vhost' => $this->generateVhost($site),
            ]),
            'create-vhost',
            $site->id
        );
    }

    public function updateVHost(Site $site, bool $noSSL = false, ?string $vhost = null): void
    {
        $this->service->server->ssh()->exec(
            $this->getScript('nginx/update-vhost.sh', [
                'domain' => $site->domain,
                'path' => $site->path,
                'vhost' => $vhost ?? $this->generateVhost($site, $noSSL),
            ]),
            'update-vhost',
            $site->id
        );
    }

    public function getVHost(Site $site): string
    {
        return $this->service->server->ssh()->exec(
            $this->getScript('nginx/get-vhost.sh', [
                'domain' => $site->domain,
            ]),
        );
    }

    public function deleteSite(Site $site): void
    {
        $this->service->server->ssh()->exec(
            $this->getScript('nginx/delete-site.sh', [
                'domain' => $site->domain,
                'path' => $site->path,
            ]),
            'delete-vhost',
            $site->id
        );
        $this->service->restart();
    }

    public function changePHPVersion(Site $site, $version): void
    {
        $this->service->server->ssh()->exec(
            $this->getScript('nginx/change-php-version.sh', [
                'domain' => $site->domain,
                'old_version' => $site->php_version,
                'new_version' => $version,
            ]),
            'change-php-version',
            $site->id
        );
    }

    /**
     * @throws SSHError
     */
    public function setupSSL(Ssl $ssl): void
    {
        $domains = '';
        foreach ($ssl->getDomains() as $domain) {
            $domains .= ' -d '.$domain;
        }
        $command = $this->getScript('nginx/create-letsencrypt-ssl.sh', [
            'email' => $ssl->site->server->creator->email,
            'domain' => $ssl->site->domain,
            'domains' => $domains,
            'web_directory' => $ssl->site->getWebDirectoryPath(),
        ]);
        if ($ssl->type == 'custom') {
            $command = $this->getScript('nginx/create-custom-ssl.sh', [
                'path' => $ssl->getCertsDirectoryPath(),
                'certificate' => $ssl->certificate,
                'pk' => $ssl->pk,
                'certificate_path' => $ssl->getCertificatePath(),
                'pk_path' => $ssl->getPkPath(),
            ]);
        }
        $result = $this->service->server->ssh()->setLog($ssl->log)->exec(
            $command,
            'create-ssl',
            $ssl->site_id
        );
        if (! $ssl->validateSetup($result)) {
            throw new SSLCreationException;
        }

        $this->updateVHost($ssl->site);
    }

    /**
     * @throws Throwable
     */
    public function removeSSL(Ssl $ssl): void
    {
        $this->service->server->ssh()->exec(
            'sudo rm -rf '.$ssl->getCertsDirectoryPath().'*',
            'remove-ssl',
            $ssl->site_id
        );

        $this->updateVHost($ssl->site, true);

        $this->service->server->systemd()->restart('nginx');
    }

    protected function generateVhost(Site $site, bool $noSSL = false): string
    {
        $ssl = $site->activeSsl;
        if ($noSSL) {
            $ssl = null;
        }
        $vhost = $this->getScript('nginx/vhost.conf');
        if ($ssl) {
            $vhost = $this->getScript('nginx/vhost-ssl.conf');
        }
        if ($site->type()->language() === 'php') {
            $vhost = $this->getScript('nginx/php-vhost.conf');
            if ($ssl) {
                $vhost = $this->getScript('nginx/php-vhost-ssl.conf');
            }
        }
        if ($site->port) {
            $vhost = $this->getScript('nginx/reverse-vhost.conf');
            if ($ssl) {
                $vhost = $this->getScript('nginx/reverse-vhost-ssl.conf');
            }
            $vhost = Str::replace('__port__', (string) $site->port, $vhost);
        }

        $php_socket = 'unix:/var/run/php/php-fpm.sock';
        if ($site->isIsolated()) {
            $php_socket = "unix:/run/php/php{$site->php_version}-fpm-{$site->user}.sock";
        }

        $vhost = Str::replace('__domain__', $site->domain, $vhost);
        $vhost = Str::replace('__aliases__', $site->getAliasesString(), $vhost);
        $vhost = Str::replace('__path__', $site->path, $vhost);
        $vhost = Str::replace('__web_directory__', $site->web_directory, $vhost);
        $vhost = Str::replace('__php_socket__', $php_socket, $vhost);

        if ($ssl) {
            $vhost = Str::replace('__certificate__', $ssl->getCertificatePath(), $vhost);
            $vhost = Str::replace('__private_key__', $ssl->getPkPath(), $vhost);
        }

        if ($site->php_version) {
            $vhost = Str::replace('__php_version__', $site->php_version, $vhost);
        }

        return $vhost;
    }
}
