<?php

namespace App\SSH\Services\Database;

use App\Exceptions\ServiceInstallationFailed;
use App\Models\Server;
use App\Models\Service;
use App\SSH\HasScripts;
use App\SSH\Services\ServiceInterface;
use App\SSH\Services\Database\Database;

abstract class AbstractDatabase implements ServiceInterface, Database
{
    use HasScripts;

    protected Service $service;

    protected Server $server;

    public function __construct(Service $service)
    {
        $this->service = $service;
        $this->server = $service->server;
    }

    /**
     * @throws ServiceInstallationFailed
     */
    public function install(): void
    {
        $version = $this->service->version;
        $command = $this->getScript($this->service->name . '/install-'.$version.'.sh');
        $this->server->ssh()->exec($command, 'install-' . $this->service->name . '-'.$version);
        $status = $this->server->systemd()->status($this->service->unit);
        $this->service->validateInstall($status);
    }
}
