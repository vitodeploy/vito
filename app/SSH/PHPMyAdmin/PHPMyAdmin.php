<?php

namespace App\SSH\PHPMyAdmin;

use App\Models\Site;
use App\SSH\HasScripts;

class PHPMyAdmin
{
    use HasScripts;

    public function install(Site $site): void
    {
        $site->server->ssh()->exec(
            $this->getScript('install.sh', [
                'version' => $site->type_data['version'],
                'path' => $site->path,
            ]),
            'install-phpmyadmin',
            $site->id
        );
    }
}
