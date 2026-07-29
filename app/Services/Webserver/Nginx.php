<?php

namespace App\Services\Webserver;

use App\Actions\Site\EnsureSiteVerificationKey;
use App\Actions\Webserver\GenerateNginxConfig;
use App\DTOs\ServiceLog;
use App\Exceptions\SSHError;
use App\Exceptions\SSLCreationException;
use App\Models\Site;
use App\Models\Ssl;
use App\Services\HasLogs;
use Throwable;

class Nginx extends AbstractWebserver implements HasLogs
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
        $this->service->server->ssh()
            ->setLog($this->service->log)
            ->exec(
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

        $this->deploySplash();

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

    public function generateVhost(Site $site, ?string $template = null): string
    {
        app(EnsureSiteVerificationKey::class)->ensure($site);

        return app(GenerateNginxConfig::class)->generate($site, $template);
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
    public function updateVHost(Site $site, ?string $vhost = null, bool $restart = false): void
    {
        if (! $vhost && ! $site->vhost_generation_enabled) {
            return;
        }

        if (! $vhost) {
            $vhost = $this->generateVhost($site);
        }

        $this->service->server->ssh()->write(
            '/etc/nginx/sites-available/'.$site->domain,
            $vhost,
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
            view('ssh.services.webserver.nginx.remove-basic-auth-file', [
                'path' => $site->htpasswdPath(),
            ]),
            'remove-basic-auth-file',
            $site->id
        );
        $this->service->server->ssh()->exec(
            view('ssh.services.webserver.nginx.delete-site', [
                'domain' => $site->domain,
                'path' => $site->basePath(),
            ]),
            'delete-vhost',
            $site->id
        );
        $this->service->reload();
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

    /**
     * @throws SSHError
     */
    public function deploySplash(): void
    {
        $ssh = $this->service->server->ssh();

        $ssh->exec(
            'sudo rm -f /etc/nginx/sites-enabled/default /etc/nginx/sites-available/default /etc/nginx/conf.d/default.conf',
            'remove-os-default-site'
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
            '/etc/nginx/sites-available/000-default',
            view('ssh.services.webserver.nginx.default-vhost'),
            'root'
        );

        $ssh->exec(
            'sudo ln -sf /etc/nginx/sites-available/000-default /etc/nginx/sites-enabled/000-default',
            'enable-default-vhost'
        );
    }

    public function versionCommand(): ?string
    {
        return 'nginx -v 2>&1 | awk -F/ \'{print $2}\';';
    }

    public function parseVersionOutput(string $output): ?string
    {
        $version = str(trim($output))->before(' ')->toString();

        return $version === '' ? null : $version;
    }

    public function logs(): array
    {
        $logs = [
            new ServiceLog(
                key: 'nginx:error',
                serviceLabel: 'NGINX',
                label: 'Error log',
                source: ServiceLog::SOURCE_FILE,
                target: '/var/log/nginx/error.log',
            ),
            new ServiceLog(
                key: 'nginx:access',
                serviceLabel: 'NGINX',
                label: 'Access log',
                source: ServiceLog::SOURCE_FILE,
                target: '/var/log/nginx/access.log',
            ),
        ];

        $sites = $this->service->server->relationLoaded('sites')
            ? $this->service->server->sites->sortBy('id')
            : $this->service->server->sites()->orderBy('id')->get(['id', 'domain']);

        foreach ($sites as $site) {
            $logs[] = new ServiceLog(
                key: 'nginx:site:'.$site->id.':error',
                serviceLabel: 'NGINX',
                label: $site->domain.' error log',
                source: ServiceLog::SOURCE_FILE,
                target: '/var/log/nginx/'.$site->domain.'-error.log',
            );
        }

        return $logs;
    }
}
