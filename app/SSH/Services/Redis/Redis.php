<?php

namespace App\SSH\Services\Redis;

use App\Models\Service;
use App\SSH\HasScripts;
use App\SSH\Services\ServiceInterface;

class Redis implements ServiceInterface
{
    use HasScripts;

    public function __construct(protected Service $service)
    {
    }

    public function install(): void
    {
        $this->service->server->ssh()->exec(
            $this->getScript('install.sh'),
            'install-redis'
        );
    }
}
