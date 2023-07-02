<?php

namespace App\Jobs\Queue;

use App\Events\Broadcast;
use App\Jobs\Job;
use App\Models\Queue;

class Manage extends Job
{
    protected Queue $worker;

    protected string $action;

    protected string $successStatus;

    protected string $failStatus;

    protected string $failMessage;

    public function __construct(
        Queue $worker,
        string $action,
        string $successStatus,
        string $failStatus,
        string $failMessage,
    ) {
        $this->worker = $worker;
        $this->action = $action;
        $this->successStatus = $successStatus;
        $this->failStatus = $failStatus;
        $this->failMessage = $failMessage;
    }

    public function handle(): void
    {
        switch ($this->action) {
            case 'start':
                $this->worker->server->processManager()->handler()->start($this->worker->id, $this->worker->site_id);
                break;
            case 'stop':
                $this->worker->server->processManager()->handler()->stop($this->worker->id, $this->worker->site_id);
                break;
            case 'restart':
                $this->worker->server->processManager()->handler()->restart($this->worker->id, $this->worker->site_id);
                break;
        }
        $this->worker->status = $this->successStatus;
        $this->worker->save();
        event(
            new Broadcast('manage-queue-finished', [
                'queue' => $this->worker,
            ])
        );
    }

    public function failed(): void
    {
        $this->worker->status = $this->failStatus;
        $this->worker->save();
        event(
            new Broadcast('manage-queue-failed', [
                'message' => $this->failMessage,
                'queue' => $this->worker,
            ])
        );
    }
}
