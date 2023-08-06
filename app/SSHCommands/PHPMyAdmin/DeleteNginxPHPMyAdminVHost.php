<?php

namespace App\SSHCommands\PHPMyAdmin;

use App\SSHCommands\Command;
use Illuminate\Support\Facades\File;

class DeleteNginxPHPMyAdminVHost extends Command
{
    public function __construct(protected string $path)
    {
    }

    public function file(): string
    {
        return File::get(resource_path('commands/phpmyadmin/delete-phpmyadmin-vhost.sh'));
    }

    public function content(): string
    {
        return str($this->file())
            ->replace('__path__', $this->path)
            ->toString();
    }
}
