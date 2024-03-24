<?php

namespace App\Actions\Queue;

use App\Models\Queue;

class GetQueueLogs
{
    public function getLogs(Queue $queue): string
    {
        return $queue->server->processManager()->handler()->getLogs($queue->getLogFile());
    }
}
