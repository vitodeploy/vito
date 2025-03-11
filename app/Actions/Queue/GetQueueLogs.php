<?php

namespace App\Actions\Queue;

use App\Models\Queue;

class GetQueueLogs
{
    public function getLogs(Queue $queue): string
    {
        $service = $queue->server->processManager();
        if (! $service) {
            throw new \Exception('Process manager service not found');
        }

        /** @var \App\SSH\Services\ProcessManager\ProcessManager $handler */
        $handler = $service->handler();

        return $handler->getLogs($queue->user, $queue->getLogFile());
    }
}
