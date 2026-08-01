<?php

namespace Tests\Feature;

use App\Exceptions\SSHCommandError;
use App\Models\ServerLog;
use App\Services\ProcessManager\Supervisor;

class ThrowingSupervisor extends Supervisor
{
    /**
     * @param  array<int, int>  $ids
     */
    public function restartMany(array $ids, ?int $siteId = null): string
    {
        $log = ServerLog::log($this->service->server, 'restart-workers', '');

        throw new SSHCommandError(message: 'restart failed', log: $log);
    }
}
