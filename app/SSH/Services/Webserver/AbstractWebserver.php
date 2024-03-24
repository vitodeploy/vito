<?php

namespace App\SSH\Services\Webserver;

use App\Models\Service;
use App\SSH\Services\ServiceInterface;

abstract class AbstractWebserver implements ServiceInterface, Webserver
{
    public function __construct(protected Service $service)
    {
    }
}
