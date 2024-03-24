<?php

namespace App\SSH\Services\Firewall;

use App\Models\Service;
use App\SSH\Services\ServiceInterface;

abstract class AbstractFirewall implements Firewall, ServiceInterface
{
    protected Service $service;

    public function __construct(Service $service)
    {
        $this->service = $service;
    }
}
