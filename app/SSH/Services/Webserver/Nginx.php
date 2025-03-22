<?php

namespace App\SSH\Services\Webserver;

use App\Exceptions\SSHError;
use App\Exceptions\SSLCreationException;
use App\Exceptions\ViewNotFoundException;
use App\Models\Site;
use App\Models\Ssl;
use Closure;
use Throwable;

class Nginx extends AbstractWebserver
{
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

        $this->service->server->systemd()->restart('nginx');

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
            view('ssh.services.webserver.nginx.uninstall-nginx'),
            'uninstall-nginx'
        );
        $this->service->server->os()->cleanup();
    }

    public function ensureConfigPathsExist(Site $site): void
    {
        $this->service->server->ssh()->ensurePathExists('/etc/nginx/conf.d/custom-configs/'.$site->id, 'root');
        $this->service->server->ssh()->ensurePathExists('/etc/nginx/conf.d/extension-configs/'.$site->id, 'root');
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
            view('ssh.services.webserver.nginx.vhost', [
                'site' => $site,
            ]),
            'root'
        );

        $this->service->server->ssh()->exec(
            view('ssh.services.webserver.nginx.create-vhost', [
                'domain' => $site->domain,
                'vhost' => view('ssh.services.webserver.nginx.vhost', [
                    'site' => $site,
                ]),
            ]),
            'create-vhost',
            $site->id
        );

        $this->ensureConfigPathsExist($site);
    }

    /**
     * @param  string  $configName  The name of the config file to create or update, without the .conf extension. If $configContent is null, the config file will be created with the contents of the view file.
     *
     * @throws SSHError|ViewNotFoundException
     */
    public function createOrUpdateAdditionalConfig(Site $site, string $configName, ?string $configContent = null, bool $restart = true): void
    {
        $this->ensureConfigPathsExist($site);

        if ($configContent) {
            $this->service->server->ssh()->write(
                '/etc/nginx/conf.d/custom-configs/'.$site->id.'/'.$configName.'.conf',
                $configContent,
                'root'
            );
        } else {
            $view = 'ssh.services.webserver.nginx.extensions.'.$configName;
            if (view()->exists($view)) {
                $this->service->server->ssh()->write(
                    '/etc/nginx/conf.d/extension-configs/'.$site->id.'/'.$configName.'.conf',
                    view($view, [
                        'site' => $site,
                    ]),
                    'root'
                );
            } else {
                throw new ViewNotFoundException($view);
            }
        }

        if ($restart) {
            $this->service->server->systemd()->restart('nginx');
        }
    }

    /**
     * @throws SSHError
     */
    public function updateVHost(Site $site, ?string $vhost = null): void
    {
        $this->ensureConfigPathsExist($site);

        $this->service->server->ssh()->write(
            '/etc/nginx/sites-available/'.$site->domain,
            $vhost ?? view('ssh.services.webserver.nginx.vhost', [
                'site' => $site,
            ]),
            'root'
        );

        $this->service->server->systemd()->restart('nginx');
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
}
