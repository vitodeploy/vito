<?php

namespace App\Actions\Server\Logs;

use App\Models\ServerLog;
use Illuminate\Validation\ValidationException;

class DeleteServerLog
{
    public function delete(ServerLog $serverLog): void
    {
        $serverLog->delete();
    }
}
