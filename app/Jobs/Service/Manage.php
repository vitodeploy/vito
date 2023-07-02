<?php

namespace App\Jobs\Service;

use App\Events\Broadcast;
use App\Jobs\Job;
use App\Models\Service;
use App\SSHCommands\RestartServiceCommand;
use App\SSHCommands\StartServiceCommand;
use App\SSHCommands\StopServiceCommand;
use Exception;
use Throwable;

class Manage extends Job
{
    protected Service $service;

    protected string $action;

    protected string $successStatus;

    protected string $failStatus;

    protected string $failMessage;

    public function __construct(
        Service $service,
        string $action,
        string $successStatus,
        string $failStatus,
        string $failMessage,
    ) {
        $this->service = $service;
        $this->action = $action;
        $this->successStatus = $successStatus;
        $this->failStatus = $failStatus;
        $this->failMessage = $failMessage;
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        $command = match ($this->action) {
            'start' => new StartServiceCommand($this->service->unit),
            'stop' => new StopServiceCommand($this->service->unit),
            'restart' => new RestartServiceCommand($this->service->unit),
            default => throw new Exception('Invalid action'),
        };
        $this->service->server->ssh()->exec(
            $command,
            $this->action.'-'.$this->service->name
        );
        $this->service->status = $this->successStatus;
        $this->service->save();
        event(
            new Broadcast('update-service-finished', [
                'service' => $this->service,
            ])
        );
    }

    public function failed(): void
    {
        $this->service->status = $this->failStatus;
        $this->service->save();
        event(
            new Broadcast('update-service-failed', [
                'message' => $this->service->name.' '.$this->failMessage,
                'service' => $this->service,
            ])
        );
    }
}
