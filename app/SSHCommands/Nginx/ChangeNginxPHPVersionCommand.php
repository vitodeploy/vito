<?php

namespace App\SSHCommands\Nginx;

use App\SSHCommands\Command;
use Illuminate\Support\Facades\File;

class ChangeNginxPHPVersionCommand extends Command
{
    public function __construct(protected string $domain, protected string $oldVersion, protected string $newVersion)
    {
    }

    public function file(): string
    {
        return File::get(resource_path('commands/webserver/nginx/change-php-version.sh'));
    }

    public function content(): string
    {
        return str($this->file())
            ->replace('__domain__', $this->domain)
            ->replace('__old_version__', $this->oldVersion)
            ->replace('__new_version__', $this->newVersion)
            ->toString();
    }
}
