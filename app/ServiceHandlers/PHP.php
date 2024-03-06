<?php

namespace App\ServiceHandlers;

use App\Jobs\PHP\InstallPHPExtension;
use App\Models\Service;
use App\SSHCommands\PHP\ChangeDefaultPHPCommand;

class PHP
{
    protected Service $service;

    public function __construct(Service $service)
    {
        $this->service = $service;
    }

    public function setDefaultCli(): void
    {
        $this->service->server->ssh()->exec(
            new ChangeDefaultPHPCommand($this->service->version),
            'change-default-php'
        );
    }

    public function installExtension($name): void
    {
        dispatch(new InstallPHPExtension($this->service, $name))->onConnection('ssh');
    }
}
