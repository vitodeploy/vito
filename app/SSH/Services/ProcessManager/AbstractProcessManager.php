<?php

namespace App\SSH\Services\ProcessManager;

use App\Models\Service;
use App\SSH\Services\ProcessManager\ProcessManager;
use App\SSH\Services\ServiceInterface;

abstract class AbstractProcessManager implements ServiceInterface, ProcessManager
{
    protected Service $service;

    public function __construct(Service $service)
    {
        $this->service = $service;
    }
}
