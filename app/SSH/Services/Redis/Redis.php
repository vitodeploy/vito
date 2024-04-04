<?php

namespace App\SSH\Services\Redis;

use App\SSH\HasScripts;
use App\SSH\Services\AbstractService;

class Redis extends AbstractService
{
    use HasScripts;

    public function install(): void
    {
        $this->service->server->ssh()->exec(
            $this->getScript('install.sh'),
            'install-redis'
        );
    }

    public function uninstall(): void
    {
        //
    }
}
