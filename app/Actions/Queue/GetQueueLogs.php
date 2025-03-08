<?php

namespace App\Actions\Queue;

use App\Models\Queue;

class GetQueueLogs
{
    public function getLogs(Queue $queue): string
    {
        /** @var \App\SSH\Services\ProcessManager\ProcessManager $handler */
        $handler = $queue->server->processManager()->handler();

        return $handler->getLogs($queue->user, $queue->getLogFile());
    }
}
