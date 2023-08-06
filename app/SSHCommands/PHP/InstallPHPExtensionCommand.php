<?php

namespace App\SSHCommands\PHP;

use App\SSHCommands\Command;
use Illuminate\Support\Facades\File;

class InstallPHPExtensionCommand extends Command
{
    public function __construct(protected string $version, protected string $name)
    {
    }

    public function file(): string
    {
        return File::get(resource_path('commands/php/install-php-extension.sh'));
    }

    public function content(): string
    {
        return str($this->file())
            ->replace('__version__', $this->version)
            ->replace('__name__', $this->name)
            ->toString();
    }
}
