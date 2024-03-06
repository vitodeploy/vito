<?php

namespace App\Jobs\Queue;

use App\Enums\QueueStatus;
use App\Jobs\Job;
use App\Models\Queue;

class Remove extends Job
{
    protected Queue $worker;

    public function __construct(Queue $worker)
    {
        $this->worker = $worker;
    }

    public function handle(): void
    {
        $this->worker->server->processManager()->handler()->delete($this->worker->id, $this->worker->site_id);
        $this->worker->delete();
    }

    public function failed(): void
    {
        $this->worker->status = QueueStatus::FAILED;
        $this->worker->save();
    }
}
