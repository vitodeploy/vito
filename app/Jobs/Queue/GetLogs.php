<?php

namespace App\Jobs\Queue;

use App\Events\Broadcast;
use App\Jobs\Job;
use App\Models\Queue;

class GetLogs extends Job
{
    protected Queue $worker;

    public function __construct(Queue $worker)
    {
        $this->worker = $worker;
    }

    public function handle(): void
    {
        $logs = $this->worker->server->processManager()->handler()->getLogs($this->worker->log_file);
        event(
            new Broadcast('get-logs-finished', [
                'id' => $this->worker->id,
                'logs' => $logs,
            ])
        );
    }

    public function failed(): void
    {
        event(
            new Broadcast('get-logs-failed', [
                'message' => __('Failed to download the logs!'),
            ])
        );
    }
}
