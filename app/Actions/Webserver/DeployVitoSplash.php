<?php

namespace App\Actions\Webserver;

use App\Exceptions\SSHError;
use App\Models\Server;

class DeployVitoSplash
{
    /**
     * @throws SSHError
     */
    public function deploy(Server $server): void
    {
        $ssh = $server->ssh();

        $ssh->exec(
            'sudo rm -f /etc/nginx/sites-enabled/default /etc/nginx/sites-available/default /etc/nginx/conf.d/default.conf',
            'remove-os-default-site'
        );

        $ssh->exec(
            'sudo mkdir -p /var/www/vito-splash',
            'create-vito-splash-dir'
        );

        $ssh->write(
            '/var/www/vito-splash/index.html',
            view('ssh.services.webserver.nginx.vito-splash'),
            'root'
        );

        $ssh->write(
            '/etc/nginx/sites-available/000-default',
            view('ssh.services.webserver.nginx.default-vhost'),
            'root'
        );

        $ssh->exec(
            'sudo ln -sf /etc/nginx/sites-available/000-default /etc/nginx/sites-enabled/000-default',
            'enable-default-vhost'
        );

        $server->systemd()->reload('nginx');
    }
}
