<?php

namespace App\Services\Webserver;

use App\Actions\Webserver\AbstractGenerateConfig;
use App\Actions\Webserver\GenerateApacheConfig;
use App\Exceptions\SSHError;
use App\Exceptions\SSLCreationException;
use App\Models\Site;
use App\Models\Ssl;
use Throwable;

class Apache extends AbstractWebserver
{
    public static function id(): string
    {
        return 'apache';
    }

    public static function type(): string
    {
        return 'webserver';
    }

    public function unit(): string
    {
        return 'apache2';
    }

    /**
     * @throws SSHError
     */
    public function install(): void
    {
        $this->service->server->ssh()
            ->setLog($this->service->log)
            ->exec(
                view('ssh.services.webserver.apache.install-apache', [
                    'user' => $this->service->server->getSshUser(),
                ]),
                'install-apache'
            );

        $this->deploySplash();

        $this->service->server->systemd()->restart('apache2');
        event('service.installed', $this->service);
        $this->service->server->os()->cleanup();
    }

    /**
     * @throws SSHError
     */
    public function uninstall(): void
    {
        $this->service->server->ssh()->exec(
            view('ssh.services.webserver.apache.uninstall-apache'),
            'uninstall-apache'
        );
        event('service.uninstalled', $this->service);
        $this->service->server->os()->cleanup();
    }

    public function configGenerator(): AbstractGenerateConfig
    {
        return app(GenerateApacheConfig::class);
    }

    public function generateVhost(Site $site, ?string $template = null): string
    {
        return $this->configGenerator()->generate($site, $template);
    }

    /**
     * @throws SSHError
     */
    public function createVHost(Site $site): void
    {
        $ssh = $this->service->server->ssh($site->user);

        $ssh->exec(
            view('ssh.services.webserver.apache.create-path', [
                'path' => $site->path,
            ]),
            'create-path',
            $site->id
        );

        $this->service->server->ssh()->write(
            '/etc/apache2/sites-available/'.$site->domain.'.conf',
            $this->generateVhost($site),
            'root'
        );

        $this->service->server->ssh()->exec(
            view('ssh.services.webserver.apache.create-vhost', [
                'domain' => $site->domain,
            ]),
            'create-vhost',
            $site->id
        );
    }

    /**
     * @throws SSHError
     */
    public function updateVHost(Site $site, ?string $vhost = null, bool $restart = false): void
    {
        if (! $vhost && ! $site->vhost_generation_enabled) {
            return;
        }

        if (! $vhost) {
            $vhost = $this->generateVhost($site);
        }

        $this->service->server->ssh()->write(
            '/etc/apache2/sites-available/'.$site->domain.'.conf',
            $vhost,
            'root'
        );

        if ($restart) {
            $this->service->server->systemd()->restart('apache2');

            return;
        }

        $this->service->server->systemd()->reload('apache2');
    }

    /**
     * @throws SSHError
     */
    public function getVHost(Site $site): string
    {
        return $this->service->server->ssh()->exec(
            view('ssh.services.webserver.apache.get-vhost', [
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
            view('ssh.services.webserver.nginx.remove-basic-auth-file', [
                'path' => $site->htpasswdPath(),
            ]),
            'remove-basic-auth-file',
            $site->id
        );
        $this->service->server->ssh()->exec(
            view('ssh.services.webserver.apache.delete-site', [
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
    public function setupSSL(Ssl $ssl): void
    {
        $domains = '';
        foreach ($ssl->getDomains() as $domain) {
            $domains .= ' -d '.$domain;
        }
        $command = view('ssh.services.webserver.apache.create-letsencrypt-ssl', [
            'email' => $ssl->email,
            'name' => $ssl->id,
            'domains' => $domains,
            'webroot' => $ssl->site->getWebDirectoryPath(),
        ]);
        if ($ssl->type == 'custom') {
            $ssl->certificate_path = '/etc/ssl/'.$ssl->id.'/cert.pem';
            $ssl->pk_path = '/etc/ssl/'.$ssl->id.'/privkey.pem';
            $ssl->save();
            $command = view('ssh.services.webserver.apache.create-custom-ssl', [
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

    /**
     * @throws SSHError
     */
    public function deploySplash(): void
    {
        $ssh = $this->service->server->ssh();

        $ssh->exec(
            'sudo a2dissite 000-default default-ssl 2>/dev/null || true',
            'disable-os-default-site'
        );

        $ssh->exec(
            'sudo mkdir -p /var/www/vito-splash',
            'create-vito-splash-dir'
        );

        $ssh->write(
            '/var/www/vito-splash/index.html',
            view('ssh.services.webserver.vito-splash'),
            'root'
        );

        $ssh->write(
            '/etc/apache2/sites-available/000-vito-default.conf',
            view('ssh.services.webserver.apache.default-vhost'),
            'root'
        );

        $ssh->exec(
            'sudo a2ensite 000-vito-default.conf',
            'enable-default-vhost'
        );
    }

    public function version(): string
    {
        $version = $this->service->server->ssh()->exec(
            'apachectl -v 2>&1 | grep -oE \'Apache/[0-9]+\.[0-9]+\.[0-9]+\' | cut -d/ -f2'
        );

        return trim($version);
    }
}
