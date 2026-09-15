<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class ServiceUninstalledEvent
{
    use Dispatchable;

    public function __construct(
        public readonly int $serviceId,
        public readonly string $serviceName,
        public readonly string $serviceType,
        public readonly int $serverId,
        public readonly int $projectId,
    ) {}
}
