<?php

namespace App\SSH\Composer;

use App\Models\Site;
use App\SSH\HasScripts;

class Composer
{
    use HasScripts;

    public function installDependencies(Site $site): void
    {
        $site->server->ssh()->exec(
            $this->getScript('composer-install.sh', [
                'path' => $site->path,
            ]),
            'composer-install',
            $site->id
        );
    }
}
