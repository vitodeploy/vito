<?php

namespace App\SSHCommands;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class InstallNginxCommand extends Command
{
    public function file(string $os): string
    {
        return File::get(base_path('system/commands/ubuntu/install-nginx.sh'));
    }

    public function content(string $os): string
    {
        return Str::replace('__config__', $this->config(), $this->file($os));
    }

    /**
     * @return string
     */
    protected function config()
    {
        $config = File::get(base_path('system/command-templates/nginx/nginx.conf'));

        return Str::replace('__user__', config('core.ssh_user'), $config);
    }
}
