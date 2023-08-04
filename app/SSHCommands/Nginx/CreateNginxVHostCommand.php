<?php

namespace App\SSHCommands\Nginx;

use App\SSHCommands\Command;
use Illuminate\Support\Facades\File;

class CreateNginxVHostCommand extends Command
{
    public function __construct(
        protected string $domain,
        protected string $path,
        protected string $vhost
    ) {
    }

    public function file(): string
    {
        return File::get(resource_path('commands/webserver/nginx/create-vhost.sh'));
    }

    public function content(): string
    {
        return str($this->file())
            ->replace('__domain__', $this->domain)
            ->replace('__path__', $this->path)
            ->replace('__vhost__', $this->vhost)
            ->toString();
    }
}
