<?php

namespace App\Actions\Queue;

use App\Models\Queue;
use App\Models\Service;
use App\SSH\Services\ProcessManager\ProcessManager;

class GetQueueLogs
{
    public function getLogs(Queue $queue): string
    {
        /** @var Service $service */
        $service = $queue->server->processManager();

        /** @var ProcessManager $handler */
        $handler = $service->handler();

        return $handler->getLogs($queue->user, $queue->getLogFile());
    }
}
