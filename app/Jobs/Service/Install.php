<?php

namespace App\Jobs\Service;

use App\Jobs\Job;
use App\Models\Service;

class Install extends Job
{
    protected Service $service;

    public function __construct(Service $service)
    {
        $this->service = $service;
    }

    public function handle()
    {
    }

    public function failed(\Throwable $throwable)
    {
    }
}
