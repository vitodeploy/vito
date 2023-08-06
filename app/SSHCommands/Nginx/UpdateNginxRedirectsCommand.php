<?php

namespace App\SSHCommands\Nginx;

use App\SSHCommands\Command;
use Illuminate\Support\Facades\File;

class UpdateNginxRedirectsCommand extends Command
{
    public function __construct(
        protected string $domain,
        protected string $redirects
    ) {
    }

    public function file(): string
    {
        return File::get(resource_path('commands/webserver/nginx/update-redirects.sh'));
    }

    public function content(): string
    {
        return str($this->file())
            ->replace('__redirects__', addslashes($this->redirects))
            ->replace('__domain__', $this->domain)
            ->toString();
    }
}
