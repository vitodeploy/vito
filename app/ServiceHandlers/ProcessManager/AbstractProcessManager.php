<?php

namespace App\ServiceHandlers\ProcessManager;

use App\Contracts\ProcessManager;
use App\Models\Service;

abstract class AbstractProcessManager implements ProcessManager
{
    protected Service $service;

    public function __construct(Service $service)
    {
        $this->service = $service;
    }
}
