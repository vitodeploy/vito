<?php

namespace App\ServiceHandlers\Firewall;

use App\Contracts\Firewall;
use App\Models\Service;

abstract class AbstractFirewall implements Firewall
{
    protected Service $service;

    public function __construct(Service $service)
    {
        $this->service = $service;
    }
}
