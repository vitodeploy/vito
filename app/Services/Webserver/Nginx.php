<?php

namespace App\Services\Webserver;

use App\Exceptions\SSHError;
use App\Exceptions\SSLCreationException;
use App\Models\Site;
use App\Models\Ssl;
use Throwable;

class Nginx extends AbstractWebserver
{
    public static function id(): string
    {
        return 'nginx';
    }

    public static function type(): string
    {
        return 'webserver';
    }

    public function unit(): string
    {
        return 'nginx';
    }

    /**
     * @throws SSHError
     */
    public function install(): void
    {
        $this->service->server->ssh()->exec(
            view('ssh.services.webserver.nginx.install-nginx'),
            'install-nginx'
        );

        $this->service->server->ssh()->write(
            '/etc/nginx/nginx.conf',
            view('ssh.services.webserver.nginx.nginx', [
                'user' => $this->service->server->getSshUser(),
            ]),
            'root'
        );

        $this->service->server->ssh()->exec(
            view('ssh.services.webserver.nginx.create-default-ssl'),
            'create-default-ssl'
        );

        $this->service->server->ssh()->write(
            '/etc/nginx/sites-available/000-default-ssl',
            view('ssh.services.webserver.nginx.default-ssl-vhost'),
            'root'
        );

        $this->service->server->ssh()->exec(
            'sudo ln -sf /etc/nginx/sites-available/000-default-ssl /etc/nginx/sites-enabled/000-default-ssl',
            'enable-default-ssl'
        );

        $this->service->server->systemd()->restart('nginx');
        event('service.installed', $this->service);
        $this->service->server->os()->cleanup();
    }

    /**
     * @throws SSHError
     */
    public function uninstall(): void
    {
        $this->service->server->ssh()->exec(
            view('ssh.services.webserver.nginx.uninstall-nginx'),
            'uninstall-nginx'
        );
        event('service.uninstalled', $this->service);
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
            view('ssh.services.webserver.nginx.create-path', [
                'path' => $site->path,
            ]),
            'create-path',
            $site->id
        );

        $this->service->server->ssh()->write(
            '/etc/nginx/sites-available/'.$site->domain,
            $this->generateVhost($site),
            'root'
        );

        $this->service->server->ssh()->exec(
            view('ssh.services.webserver.nginx.create-vhost', [
                'domain' => $site->domain,
                'vhost' => $this->generateVhost($site),
            ]),
            'create-vhost',
            $site->id
        );
    }

    /**
     * @throws SSHError
     */
    public function updateVHost(Site $site, ?string $vhost = null, array $replace = [], array $regenerate = [], array $append = [], bool $restart = true): void
    {
        if (! $vhost) {
            $vhost = $this->getVHost($site);
        }

        if (! $vhost || ! preg_match('/#\[header]/', $vhost) || ! preg_match('/#\[main]/', $vhost) || ! preg_match('/#\[footer]/', $vhost)) {
            $vhost = $this->generateVhost($site);
        }

        $this->service->server->ssh()->write(
            '/etc/nginx/sites-available/'.$site->domain,
            format_nginx_config($this->getUpdatedVHost($site, $vhost, $replace, $regenerate, $append)),
            'root'
        );

        if ($restart) {
            $this->service->server->systemd()->restart('nginx');

            return;
        }

        $this->service->server->systemd()->reload('nginx');
    }

    /**
     * @throws SSHError
     */
    public function getVHost(Site $site): string
    {
        return $this->service->server->ssh()->exec(
            view('ssh.services.webserver.nginx.get-vhost', [
                'domain' => $site->domain,
            ]),
        );
    }

    /**
     * @throws SSHError
     */
    public function deleteSite(Site $site): void
    {
        $this->service->server->ssh()->exec(
            view('ssh.services.webserver.nginx.delete-site', [
                'domain' => $site->domain,
                'path' => $site->basePath(),
            ]),
            'delete-vhost',
            $site->id
        );
        $this->service->restart();
    }

    /**
     * @throws SSHError
     */
    public function changePHPVersion(Site $site, string $version): void
    {
        $this->service->server->ssh()->exec(
            view('ssh.services.webserver.nginx.change-php-version', [
                'domain' => $site->domain,
                'oldVersion' => $site->php_version,
                'newVersion' => $version,
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
        $command = view('ssh.services.webserver.nginx.create-letsencrypt-ssl', [
            'email' => $ssl->email,
            'name' => $ssl->id,
            'domains' => $domains,
        ]);
        if ($ssl->type == 'custom') {
            $ssl->certificate_path = '/etc/ssl/'.$ssl->id.'/cert.pem';
            $ssl->pk_path = '/etc/ssl/'.$ssl->id.'/privkey.pem';
            $ssl->save();
            $command = view('ssh.services.webserver.nginx.create-custom-ssl', [
                'path' => dirname($ssl->certificate_path),
                'certificate' => $ssl->certificate,
                'pk' => $ssl->pk,
                'certificatePath' => $ssl->certificate_path,
                'pkPath' => $ssl->pk_path,
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
    }

    /**
     * @throws Throwable
     */
    public function removeSSL(Ssl $ssl): void
    {
        if ($ssl->certificate_path) {
            $this->service->server->ssh()->exec(
                'sudo rm -rf '.dirname($ssl->certificate_path),
                'remove-ssl',
                $ssl->site_id
            );
        }

        $this->updateVHost($ssl->site);
    }

    public function version(): string
    {
        $version = $this->service->server->ssh()->exec(
            'nginx -v 2>&1 | awk -F/ \'{print $2}\';'
        );

        return str(trim($version))->before(' ');
    }
}
