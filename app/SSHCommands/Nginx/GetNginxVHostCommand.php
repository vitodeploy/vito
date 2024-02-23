<?php

namespace App\SSHCommands\Nginx;

use App\SSHCommands\Command;
use Illuminate\Support\Facades\File;

class GetNginxVHostCommand extends Command
{
    public function __construct(
        protected string $domain
    ) {
    }

    public function file(): string
    {
        return File::get(resource_path('commands/webserver/nginx/get-vhost.sh'));
    }

    public function content(): string
    {
        return str($this->file())
            ->replace('__domain__', $this->domain)
            ->toString();
    }
}
