<?php

namespace App\SSHCommands\PHP;

use App\SSHCommands\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class UninstallPHPCommand extends Command
{
    public function __construct(protected string $version)
    {
    }

    public function file(): string
    {
        return File::get(resource_path('commands/php/uninstall-php.sh'));
    }

    public function content(): string
    {
        /** TODO: change user to server default user */
        return str($this->file())
            ->replace('__version__', $this->version)
            ->replace('__user__', config('core.ssh_user'))
            ->toString();
    }
}
