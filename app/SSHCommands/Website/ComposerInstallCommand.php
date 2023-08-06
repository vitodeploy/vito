<?php

namespace App\SSHCommands\Website;

use App\SSHCommands\Command;
use Illuminate\Support\Facades\File;

class ComposerInstallCommand extends Command
{
    public function __construct(protected string $path)
    {
    }

    public function file(): string
    {
        return File::get(resource_path('commands/website/composer-install.sh'));
    }

    public function content(): string
    {
        return str($this->file())
            ->replace('__path__', $this->path)
            ->toString();
    }
}
