<?php

namespace App\ServiceHandlers\Webserver;

use App\Exceptions\ErrorUpdatingRedirects;
use App\Exceptions\SSLCreationException;
use App\Models\Site;
use App\Models\Ssl;
use App\SSHCommands\Nginx\ChangeNginxPHPVersionCommand;
use App\SSHCommands\Nginx\CreateNginxVHostCommand;
use App\SSHCommands\Nginx\DeleteNginxSiteCommand;
use App\SSHCommands\Nginx\UpdateNginxRedirectsCommand;
use App\SSHCommands\Nginx\UpdateNginxVHostCommand;
use App\SSHCommands\SSL\CreateCustomSSLCommand;
use App\SSHCommands\SSL\CreateLetsencryptSSLCommand;
use App\SSHCommands\SSL\RemoveSSLCommand;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Throwable;

class Nginx extends AbstractWebserver
{
    /**
     * @throws Throwable
     */
    public function createVHost(Site $site): void
    {
        $this->service->server->ssh()->exec(
            new CreateNginxVHostCommand(
                $site->domain,
                $site->path,
                $this->generateVhost($site)
            ),
            'create-vhost',
            $site->id
        );
    }

    /**
     * @throws Throwable
     */
    public function updateVHost(Site $site, bool $noSSL = false): void
    {
        $this->service->server->ssh()->exec(
            new UpdateNginxVHostCommand(
                $site->domain,
                $site->path,
                $this->generateVhost($site, $noSSL)
            ),
            'update-vhost',
            $site->id
        );
    }

    /**
     * @throws Throwable
     */
    public function deleteSite(Site $site): void
    {
        $this->service->server->ssh()->exec(
            new DeleteNginxSiteCommand(
                $site->domain,
                $site->path
            ),
            'delete-site',
            $site->id
        );
        $this->service->restart();
    }

    /**
     * @throws Throwable
     */
    public function changePHPVersion(Site $site, $version): void
    {
        $this->service->server->ssh()->exec(
            new ChangeNginxPHPVersionCommand($site->domain, $site->php_version, $version),
            'change-php-version',
            $site->id
        );
    }

    /**
     * @throws Throwable
     */
    public function setupSSL(Ssl $ssl): void
    {
        $command = new CreateLetsencryptSSLCommand(
            $ssl->site->server->creator->email,
            $ssl->site->domain,
            $ssl->site->web_directory_path
        );
        if ($ssl->type == 'custom') {
            $command = new CreateCustomSSLCommand(
                $ssl->certs_directory_path,
                $ssl->certificate,
                $ssl->pk,
                $ssl->certificate_path,
                $ssl->pk_path,
            );
        }
        $result = $this->service->server->ssh()->exec(
            $command,
            'create-ssl',
            $ssl->site_id
        );
        if (! $ssl->validateSetup($result)) {
            throw new SSLCreationException();
        }

        $this->updateVHost($ssl->site);
    }

    /**
     * @throws Throwable
     */
    public function removeSSL(Ssl $ssl): void
    {
        $this->service->server->ssh()->exec(
            new RemoveSSLCommand($ssl->certs_directory_path),
            'remove-ssl',
            $ssl->site_id
        );

        $this->updateVHost($ssl->site, true);
    }

    /**
     * @throws Throwable
     */
    public function updateRedirects(Site $site, array $redirects): void
    {
        $redirectsPlain = '';
        foreach ($redirects as $redirect) {
            $rd = File::get(resource_path('commands/webserver/nginx/redirect.conf'));
            $rd = Str::replace('__from__', $redirect->from, $rd);
            $rd = Str::replace('__mode__', $redirect->mode, $rd);
            $rd = Str::replace('__to__', $redirect->to, $rd);
            $redirectsPlain .= $rd."\n";
        }
        $result = $this->service->server->ssh()->exec(
            new UpdateNginxRedirectsCommand(
                $site->domain,
                $redirectsPlain,
            ),
            'update-redirects',
            $site->id
        );
        if (Str::contains($result, 'journalctl -xe')) {
            throw new ErrorUpdatingRedirects();
        }
    }

    protected function generateVhost(Site $site, bool $noSSL = false): string
    {
        $ssl = $site->activeSsl;
        if ($noSSL) {
            $ssl = null;
        }
        $vhost = File::get(resource_path('commands/webserver/nginx/vhost.conf'));
        if ($ssl) {
            $vhost = File::get(resource_path('commands/webserver/nginx/vhost-ssl.conf'));
        }
        if ($site->type()->language() === 'php') {
            $vhost = File::get(resource_path('commands/webserver/nginx/php-vhost.conf'));
            if ($ssl) {
                $vhost = File::get(resource_path('commands/webserver/nginx/php-vhost-ssl.conf'));
            }
        }
        if ($site->port) {
            $vhost = File::get(resource_path('commands/webserver/nginx/reverse-vhost.conf'));
            if ($ssl) {
                $vhost = File::get(resource_path('commands/webserver/nginx/reverse-vhost-ssl.conf'));
            }
            $vhost = Str::replace('__port__', (string) $site->port, $vhost);
        }

        $vhost = Str::replace('__domain__', $site->domain, $vhost);
        $vhost = Str::replace('__aliases__', $site->aliases_string, $vhost);
        $vhost = Str::replace('__path__', $site->path, $vhost);
        $vhost = Str::replace('__web_directory__', $site->web_directory, $vhost);

        if ($ssl) {
            $vhost = Str::replace('__certificate__', $ssl->certificate_path, $vhost);
            $vhost = Str::replace('__private_key__', $ssl->pk_path, $vhost);
        }

        if ($site->php_version) {
            $vhost = Str::replace('__php_version__', $site->php_version, $vhost);
        }

        return $vhost;
    }
}
