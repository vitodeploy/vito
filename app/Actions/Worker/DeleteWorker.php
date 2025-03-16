<?php

namespace App\Actions\Worker;

use App\Models\Worker;

class DeleteWorker
{
    public function delete(Worker $worker): void
    {
        $worker->delete();
    }
}
