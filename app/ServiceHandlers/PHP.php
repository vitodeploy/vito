<?php

namespace App\ServiceHandlers;

use App\Enums\ServiceStatus;
use App\Jobs\PHP\InstallPHPExtension;
use App\Jobs\PHP\SetDefaultCli;
use App\Jobs\PHP\UpdatePHPSettings;
use App\Models\Service;

class PHP
{
    protected Service $service;

    public function __construct(Service $service)
    {
        $this->service = $service;
    }

    public function setDefaultCli(): void
    {
        $this->service->update(['status' => ServiceStatus::RESTARTING]);

        dispatch(new SetDefaultCli($this->service))->onConnection('ssh');
    }

    public function installExtension($name): void
    {
        dispatch(new InstallPHPExtension($this->service, $name))->onConnection('ssh-long');
    }

    public function updateSettings(array $settings): void
    {
        dispatch(new UpdatePHPSettings($this->service, $settings))->onConnection('ssh-long');
    }
}
