<?php

namespace App\Events;

use App\Models\Service;
use Illuminate\Foundation\Events\Dispatchable;

class ServiceInstalledEvent
{
    use Dispatchable;

    public function __construct(
        public readonly Service $service,
    ) {}
}
