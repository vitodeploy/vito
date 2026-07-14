<?php

namespace App\Events;

use App\Enums\ServiceStatus;
use App\Models\Service;
use Illuminate\Foundation\Events\Dispatchable;

class ServiceStatusChanged
{
    use Dispatchable;

    public function __construct(
        public readonly Service $service,
        public readonly ServiceStatus $previousStatus,
        public readonly ServiceStatus $newStatus,
    ) {}
}
