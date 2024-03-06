<?php

namespace App\Jobs\Queue;

use App\Enums\QueueStatus;
use App\Jobs\Job;
use App\Models\Queue;

class Deploy extends Job
{
    protected Queue $worker;

    public function __construct(Queue $worker)
    {
        $this->worker = $worker;
    }

    public function handle(): void
    {
        $this->worker->server->processManager()->handler()->create(
            $this->worker->id,
            $this->worker->command,
            $this->worker->user,
            $this->worker->auto_start,
            $this->worker->auto_restart,
            $this->worker->numprocs,
            $this->worker->log_file,
            $this->worker->site_id
        );
        $this->worker->status = QueueStatus::RUNNING;
        $this->worker->save();
    }

    public function failed(): void
    {
        $this->worker->delete();
    }
}
