<?php

namespace App\ServiceHandlers\Webserver;

use App\Contracts\Webserver;
use App\Models\Service;

abstract class AbstractWebserver implements Webserver
{
    protected Service $service;

    public function __construct(Service $service)
    {
        $this->service = $service;
    }
}
