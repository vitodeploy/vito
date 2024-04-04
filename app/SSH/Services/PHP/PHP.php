<?php

namespace App\SSH\Services\PHP;

use App\Exceptions\SSHCommandError;
use App\SSH\HasScripts;
use App\SSH\Services\AbstractService;
use Illuminate\Support\Str;

class PHP extends AbstractService
{
    use HasScripts;

    public function install(): void
    {
        $server = $this->service->server;
        $server->ssh()->exec(
            $this->getScript('install-php.sh', [
                'version' => $this->service->version,
                'user' => $server->getSshUser(),
            ]),
            'install-php-'.$this->service->version
        );
    }

    public function uninstall(): void
    {
        $this->service->server->ssh()->exec(
            $this->getScript('uninstall-php.sh', [
                'version' => $this->service->version,
            ]),
            'uninstall-php-'.$this->service->version
        );
    }

    public function setDefaultCli(): void
    {
        $this->service->server->ssh()->exec(
            $this->getScript('change-default-php.sh', [
                'version' => $this->service->version,
            ]),
            'change-default-php'
        );
    }

    /**
     * @throws SSHCommandError
     */
    public function installExtension($name): void
    {
        $result = $this->service->server->ssh()->exec(
            $this->getScript('install-php-extension.sh', [
                'version' => $this->service->version,
                'name' => $name,
            ]),
            'install-php-extension-'.$name
        );
        $result = Str::substr($result, strpos($result, '[PHP Modules]'));
        if (! Str::contains($result, $name)) {
            throw new SSHCommandError('Failed to install extension');
        }
    }

    public function installComposer(): void
    {
        $this->service->server->ssh()->exec(
            $this->getScript('install-composer.sh'),
            'install-composer'
        );
    }

    public function getPHPIni(): string
    {
        return $this->service->server->ssh()->exec(
            $this->getScript('get-php-ini.sh', [
                'version' => $this->service->version,
            ])
        );
    }
}
