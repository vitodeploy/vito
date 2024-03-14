<?php

namespace App\SSH\Services\Database;

use App\Models\Server;
use App\Models\Service;
use App\SSH\HasScripts;
use App\SSH\Services\ServiceInterface;

abstract class AbstractDatabase implements Database, ServiceInterface
{
    use HasScripts;

    protected Service $service;

    protected Server $server;

    public function __construct(Service $service)
    {
        $this->service = $service;
        $this->server = $service->server;
    }

    public function install(): void
    {
        $version = $this->service->version;
        $command = $this->getScript($this->service->name.'/install-'.$version.'.sh');
        $this->server->ssh()->exec($command, 'install-'.$this->service->name.'-'.$version);
        $status = $this->server->systemd()->status($this->service->unit);
        $this->service->validateInstall($status);
    }
}
