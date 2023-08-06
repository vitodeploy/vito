<?php

namespace App\SSHCommands\PHP;

use App\SSHCommands\Command;
use Illuminate\Support\Facades\File;

class ChangeDefaultPHPCommand extends Command
{
    public function __construct(protected string $version)
    {
    }

    public function file(): string
    {
        return File::get(resource_path('commands/php/change-default-php.sh'));
    }

    public function content(): string
    {
        return str($this->file())
            ->replace('__version__', $this->version)
            ->toString();
    }
}
