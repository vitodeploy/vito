<?php

namespace App\SSHCommands\PHPMyAdmin;

use App\SSHCommands\Command;
use Illuminate\Support\Facades\File;

class CreateNginxPHPMyAdminVHostCommand extends Command
{
    public function __construct(protected string $vhost)
    {
    }

    public function file(): string
    {
        return File::get(resource_path('commands/phpmyadmin/create-phpmyadmin-vhost.sh'));
    }

    public function content(): string
    {
        return str($this->file())
            ->replace('__vhost__', $this->vhost)
            ->toString();
    }
}
