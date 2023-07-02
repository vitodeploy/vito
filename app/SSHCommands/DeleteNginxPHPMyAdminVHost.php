<?php

namespace App\SSHCommands;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class DeleteNginxPHPMyAdminVHost extends Command
{
    /**
     * @var string
     */
    protected $path;

    public function __construct(
        string $path,
    ) {
        $this->path = $path;
    }

    public function file(string $os): string
    {
        return File::get(base_path('system/commands/ubuntu/webserver/nginx/delete-phpmyadmin-vhost.sh'));
    }

    public function content(string $os): string
    {
        return Str::replace('__path__', $this->path, $this->file($os));
    }
}
