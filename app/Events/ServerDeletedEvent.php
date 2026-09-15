<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class ServerDeletedEvent
{
    use Dispatchable;

    public function __construct(
        public readonly int $serverId,
        public readonly string $serverName,
        public readonly string $serverIp,
        public readonly int $projectId,
    ) {}
}
