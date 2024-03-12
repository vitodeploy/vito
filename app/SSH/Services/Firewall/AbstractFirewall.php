<?php

namespace App\SSH\Services\Firewall;

use App\Models\Service;
use App\SSH\Services\ServiceInterface;
use App\SSH\Services\Firewall\Firewall;

abstract class AbstractFirewall implements ServiceInterface, Firewall
{
    protected Service $service;

    public function __construct(Service $service)
    {
        $this->service = $service;
    }
}
