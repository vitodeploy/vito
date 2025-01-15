<?php

namespace App\Actions\Queue;

use App\Models\Queue;
use App\Models\Site;

class GetQueueLogs
{
    public function getLogs(Queue $queue, Site $site): string
    {
        return $queue->server->processManager()->handler()->getLogs($site, $queue->getLogFile());
    }
}
