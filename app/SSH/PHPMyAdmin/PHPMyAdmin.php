<?php

namespace App\SSH\PHPMyAdmin;

use App\Models\Site;
use App\SSH\HasScripts;

class PHPMyAdmin
{
    use HasScripts;

    public function install(Site $site): void
    {
        $ssh = $site->server->ssh();
        if ($site->is_isolated) { $ssh->asUser($site->isolated_username); }

        $ssh->exec(
            $this->getScript('install.sh', [
                'version' => $site->type_data['version'],
                'path' => $site->path,
            ]),
            'install-phpmyadmin',
            $site->id
        );
    }
}
