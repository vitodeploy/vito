<?php

namespace App\SSHCommands\PHP;

use App\SSHCommands\Command;
use Illuminate\Support\Facades\File;

class InstallComposerCommand extends Command
{
    public function file(): string
    {
        return File::get(resource_path('commands/php/install-composer.sh'));
    }

    public function content(): string
    {
        return $this->file();
    }
}
