<?php

namespace App\Actions\Server\Logs;

use App\Models\ServerLog;

class DeleteServerLog
{
    public function delete(ServerLog $serverLog): void
    {
        $serverLog->delete();
    }
}
