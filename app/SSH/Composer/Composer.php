<?php

namespace App\SSH\Composer;

use App\Exceptions\SSHError;
use App\Models\Site;

class Composer
{
    /**
     * @throws SSHError
     */
    public function installDependencies(Site $site): void
    {
        $site->server->ssh($site->user)->exec(
            view('ssh.composer.composer-install', [
                'path' => $site->path,
                'phpVersion' => $site->php_version,
            ]),
            'composer-install',
            $site->id
        );
    }
}
