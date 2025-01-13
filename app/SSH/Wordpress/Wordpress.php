<?php

namespace App\SSH\Wordpress;

use App\Models\Site;
use App\SSH\HasScripts;

class Wordpress
{
    use HasScripts;

    public function install(Site $site): void
    {
        $ssh = $site->server->ssh();
        if ($site->is_isolated) { $ssh->asUser($site->isolated_username); }

        $ssh->exec(
            $this->getScript('install.sh', [
                'path' => $site->path,
                'domain' => $site->domain,
                'is_isolated' => $site->is_isolated ? 'true' : 'false',
                'isolated_username' => $site->isolated_username,
                'db_name' => $site->type_data['database'],
                'db_user' => $site->type_data['database_user'],
                'db_pass' => $site->type_data['database_password'],
                'db_host' => 'localhost',
                'db_prefix' => 'wp_',
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
