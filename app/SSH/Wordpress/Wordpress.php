<?php

namespace App\SSH\Wordpress;

use App\Exceptions\SSHError;
use App\Models\Site;

class Wordpress
{
    /**
     * @throws SSHError
     */
    public function install(Site $site): void
    {
        $site->server->ssh($site->user)->exec(
            view('ssh.wordpress.install', [
                'path' => $site->path,
                'domain' => $site->domain,
                'isIsolated' => $site->isIsolated() ? 'true' : 'false',
                'isolatedUsername' => $site->user,
                'dbName' => $site->type_data['database'],
                'dbUser' => $site->type_data['database_user'],
                'dbPass' => $site->type_data['database_password'],
                'dbHost' => 'localhost',
                'dbPrefix' => 'wp_',
                'username' => $site->type_data['username'],
                'password' => $site->type_data['password'],
                'email' => $site->type_data['email'],
                'title' => $site->type_data['title'],
            ]),
            'install-wordpress',
            $site->id
        );
    }
}
