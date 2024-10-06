<?php

namespace App\Actions\Queue;

use App\Models\Queue;
use App\SSH\Services\ProcessManager\ProcessManager;

class DeleteQueue
{
    public function delete(Queue $queue): void
    {
        /** @var ProcessManager $processManager */
        $processManager = $queue->server->processManager()->handler();
        $processManager->delete($queue->id, $queue->site_id);
        $queue->delete();
    }
}
