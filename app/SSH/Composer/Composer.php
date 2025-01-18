<?php

namespace App\SSH\Composer;

use App\Models\Site;
use App\SSH\HasScripts;

class Composer
{
    use HasScripts;

    public function installDependencies(Site $site): void
    {
        $site->server->ssh($site->user)->exec(
            $this->getScript('composer-install.sh', [
                'path' => $site->path,
                'php_version' => $site->php_version,
            ]),
            'composer-install',
            $site->id
        );
    }
}
