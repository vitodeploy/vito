<?php

namespace App\SSHCommands;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CreateNginxPHPMyAdminVHostCommand extends Command
{
    /**
     * @var string
     */
    protected $vhost;

    public function __construct(string $vhost)
    {
        $this->vhost = $vhost;
    }

    public function file(string $os): string
    {
        return File::get(base_path('system/commands/ubuntu/webserver/nginx/create-phpmyadmin-vhost.sh'));
    }

    public function content(string $os): string
    {
        return Str::replace('__vhost__', $this->vhost, $this->file($os));
    }
}
