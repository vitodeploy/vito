<?php

namespace App\SSH\Services\Webserver;

use App\Exceptions\SSHError;
use App\Exceptions\SSLCreationException;
use App\Models\Site;
use App\Models\Ssl;
use Closure;
use Throwable;

class Caddy extends AbstractWebserver
{
    /**
     * @throws SSHError
     */
    public function install(): void
    {
        $this->service->server->ssh()->exec(
            view('ssh.services.webserver.caddy.install-caddy'),
            'install-caddy'
        );

        $this->service->server->ssh()->write(
            '/etc/caddy/Caddyfile',
            view('ssh.services.webserver.caddy.caddy'),
            'root'
        );

        $this->service->server->ssh()->write(
            '/etc/systemd/system/caddy.service',
            view('ssh.services.webserver.caddy.caddy-systemd'),
            'root'
        );

        $this->service->server->systemd()->reload();

        $this->service->server->systemd()->restart('caddy');

        $this->service->server->os()->cleanup();
    }

    public function deletionRules(): array
    {
        return [
            'service' => [
                function (string $attribute, mixed $value, Closure $fail): void {
                    $hasSite = $this->service->server->sites()
                        ->exists();
                    if ($hasSite) {
                        $fail('Cannot uninstall webserver while you have websites using it.');
                    }
                },
            ],
        ];
    }

    /**
     * @throws SSHError
     */
    public function uninstall(): void
    {
        $this->service->server->ssh()->exec(
            view('ssh.services.webserver.caddy.uninstall-caddy'),
            'uninstall-caddy'
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
            view('ssh.services.webserver.caddy.create-path', [
                'path' => $site->path,
            ]),
            'create-path',
            $site->id
        );

        $this->service->server->ssh()->write(
            '/etc/caddy/sites-available/'.$site->domain,
            $this->generateVhost($site),
            'root'
        );

        $this->service->server->ssh()->exec(
            view('ssh.services.webserver.caddy.create-vhost', [
                'domain' => $site->domain,
            ]),
            'create-vhost',
            $site->id
        );
    }

    /**
     * @throws SSHError
     */
    public function updateVHost(Site $site, ?string $vhost = null): void
    {
        $this->service->server->ssh()->write(
            '/etc/caddy/sites-available/'.$site->domain,
            $vhost ?? $this->generateVhost($site),
            'root'
        );

        $this->service->server->systemd()->restart('caddy');
    }

    /**
     * @throws SSHError
     */
    public function getVHost(Site $site): string
    {
        return $this->service->server->ssh()->exec(
            view('ssh.services.webserver.caddy.get-vhost', [
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
            view('ssh.services.webserver.caddy.delete-site', [
                'domain' => $site->domain,
                'path' => $site->path,
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
            view('ssh.services.webserver.caddy.change-php-version', [
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
        if ($ssl->type == 'custom') {
            $ssl->certificate_path = '/etc/ssl/'.$ssl->id.'/cert.pem';
            $ssl->pk_path = '/etc/ssl/'.$ssl->id.'/privkey.pem';
            $ssl->save();
            $command = view('ssh.services.webserver.caddy.create-custom-ssl', [
                'path' => dirname($ssl->certificate_path),
                'certificate' => $ssl->certificate,
                'pk' => $ssl->pk,
                'certificatePath' => $ssl->certificate_path,
                'pkPath' => $ssl->pk_path,
            ]);
            $result = $this->service->server->ssh()->setLog($ssl->log)->exec(
                $command,
                'create-ssl',
                $ssl->site_id
            );
            if (! $ssl->validateSetup($result)) {
                throw new SSLCreationException;
            }
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

    private function generateVhost(Site $site): string
    {
        $vhost = view('ssh.services.webserver.caddy.vhost', [
            'site' => $site,
        ]);

        return format_nginx_config($vhost);
    }
}
