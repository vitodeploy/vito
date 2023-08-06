<?php

namespace App\Jobs\Installation;

use App\Enums\ServiceStatus;
use App\Exceptions\InstallationFailed;
use App\Models\Service;
use App\SSHCommands\PHP\InstallPHPCommand;
use App\SSHCommands\Service\ServiceStatusCommand;
use Throwable;

class InstallPHP extends InstallationJob
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
        $ssh->exec(new InstallPHPCommand($this->service->version), 'install-php');
        $status = $ssh->exec(new ServiceStatusCommand($this->service->unit), 'php-status');
        $this->service->validateInstall($status);
        $this->service->update([
            'status' => ServiceStatus::READY,
        ]);
    }
}
