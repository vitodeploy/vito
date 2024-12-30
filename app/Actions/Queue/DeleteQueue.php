<?php

namespace App\Actions\Queue;

use App\Models\Queue;

class DeleteQueue
{
    public function delete(Queue $queue): void
    {
        $queue->delete();
    }
}
