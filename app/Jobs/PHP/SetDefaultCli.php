<?php

namespace App\Jobs\PHP;

use App\Enums\ServiceStatus;
use App\Events\Broadcast;
use App\Jobs\Job;
use App\Models\Service;
use App\SSHCommands\ChangeDefaultPHPCommand;
use Throwable;

class SetDefaultCli extends Job
{
    protected Service $service;

    public function __construct(Service $service)
    {
        $this->service = $service;
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        $this->service->server->ssh()->exec(
            new ChangeDefaultPHPCommand($this->service->version),
            'change-default-php'
        );
        $this->service->server->defaultService('php')->update(['is_default' => 0]);
        $this->service->update(['is_default' => 1]);
        $this->service->update(['status' => ServiceStatus::READY]);
        event(
            new Broadcast('set-default-cli-finished', [
                'defaultPHP' => $this->service->server->defaultService('php'),
            ])
        );
    }

    public function failed(): void
    {
        event(new Broadcast('set-default-cli-failed', [
            'defaultPHP' => $this->service->server->defaultService('php'),
        ]));
    }
}
