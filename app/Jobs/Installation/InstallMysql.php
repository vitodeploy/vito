<?php

namespace App\Jobs\Installation;

use App\Enums\ServiceStatus;
use App\Exceptions\InstallationFailed;
use App\Models\Service;
use App\SSHCommands\Database\InstallMysqlCommand;
use App\SSHCommands\Service\ServiceStatusCommand;
use Throwable;

class InstallMysql extends InstallationJob
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
        $ssh->exec(new InstallMysqlCommand($this->service->version), 'install-mysql');
        $status = $ssh->exec(new ServiceStatusCommand($this->service->unit), 'mysql-status');
        $this->service->validateInstall($status);
        $this->service->update([
            'status' => ServiceStatus::READY,
        ]);
    }
}
