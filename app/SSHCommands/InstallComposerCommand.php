<?php

namespace App\SSHCommands;

use Illuminate\Support\Facades\File;

class InstallComposerCommand extends Command
{
    public function file(string $os): string
    {
        return File::get(base_path('system/commands/common/install-composer.sh'));
    }

    public function content(string $os): string
    {
        return $this->file($os);
    }
}
