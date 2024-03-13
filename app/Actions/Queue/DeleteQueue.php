<?php

namespace App\Actions\Queue;

use App\Models\Queue;

class DeleteQueue
{
    public function delete(Queue $queue): void
    {
        $queue->server->processManager()->handler()->delete($queue->id, $queue->site_id);
        $queue->delete();
    }
}
