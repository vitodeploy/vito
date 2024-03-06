<?php

namespace App\Jobs\Queue;

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
    }

    public function failed(): void
    {

    }
}
