<?php

namespace App\SSH\Services\Postfix;

use App\Models\Service;
use App\SSH\HasScripts;
use App\SSH\Services\ServiceInterface;

class Postfix implements ServiceInterface
{
    use HasScripts;

    public function __construct(protected Service $service)
    {
    }

    public function install(): void
    {
        $this->service->server->ssh()->exec(
            $this->getScript('install.sh'),
            'install-postfix'
        );
    }
}
