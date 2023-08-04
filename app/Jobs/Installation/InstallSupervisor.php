<?php

namespace App\Jobs\Installation;

use App\Enums\ServiceStatus;
use App\Exceptions\InstallationFailed;
use App\Models\Service;
use App\SSHCommands\Service\ServiceStatusCommand;
use App\SSHCommands\Supervisor\InstallSupervisorCommand;
use Throwable;

class InstallSupervisor extends InstallationJob
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
        $ssh->exec(new InstallSupervisorCommand(), 'install-supervisor');
        $status = $ssh->exec(new ServiceStatusCommand($this->service->unit), 'supervisor-status');
        $this->service->validateInstall($status);
        $this->service->update([
            'status' => ServiceStatus::READY,
        ]);
    }
}
