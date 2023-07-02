<?php

namespace App\Jobs\Installation;

use App\Enums\ServiceStatus;
use App\Exceptions\InstallationFailed;
use App\Models\Service;
use App\SSHCommands\InstallNginxCommand;
use App\SSHCommands\ServiceStatusCommand;
use Throwable;

class InstallNginx extends InstallationJob
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
        $ssh->exec(new InstallNginxCommand(), 'install-nginx');
        $status = $ssh->exec(new ServiceStatusCommand($this->service->unit), 'nginx-status');
        $this->service->validateInstall($status);
        $this->service->update([
            'status' => ServiceStatus::READY,
        ]);
    }
}
