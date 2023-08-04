<?php

namespace App\Jobs\Installation;

use App\Exceptions\InstallationFailed;
use App\Models\Service;
use App\SSHCommands\PHP\UninstallPHPCommand;
use Throwable;

class UninstallPHP extends InstallationJob
{
    protected Service $service;

    public function __construct(Service $service)
    {
        $this->service = $service;
    }

    /**
     * @throws InstallationFailed
     * @throws Throwable
     */
    public function handle(): void
    {
        $ssh = $this->service->server->ssh();
        $ssh->exec(new UninstallPHPCommand($this->service->version), 'uninstall-php');
    }
}
