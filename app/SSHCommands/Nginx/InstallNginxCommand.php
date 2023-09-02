<?php

namespace App\SSHCommands\Nginx;

use App\SSHCommands\Command;
use Illuminate\Support\Facades\File;

class InstallNginxCommand extends Command
{
    public function file(): string
    {
        return File::get(resource_path('commands/webserver/nginx/install-nginx.sh'));
    }

    public function content(): string
    {
        return str($this->file())
            ->replace('__config__', $this->config())
            ->toString();
    }

    protected function config(): string
    {
        $config = File::get(resource_path('commands/webserver/nginx/nginx.conf'));

        /** TODO: change user to server user */
        return str($config)
            ->replace('__user__', config('core.ssh_user'))
            ->toString();
    }
}
