<?php

namespace App\ServiceHandlers\Database;

use App\Contracts\Database;
use App\Models\Service;

abstract class AbstractDatabase implements Database
{
    protected Service $service;

    public function __construct(Service $service)
    {
        $this->service = $service;
    }
}
