<?php

namespace App\SSH\PHPMyAdmin;

use App\Exceptions\SSHError;
use App\Models\Site;

class PHPMyAdmin
{
    /**
     * @throws SSHError
     */
    public function install(Site $site): void
    {
        $site->server->ssh($site->user)->exec(
            view('ssh.phpmyadmin.install', [
                'version' => $site->type_data['version'],
                'path' => $site->path,
            ]),
            'install-phpmyadmin',
            $site->id
        );
    }
}
