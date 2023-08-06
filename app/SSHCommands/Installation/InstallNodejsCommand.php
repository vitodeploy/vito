<?php

namespace App\SSHCommands\Installation;

use App\SSHCommands\Command;
use Illuminate\Support\Facades\File;

class InstallNodejsCommand extends Command
{
    public function file(): string
    {
        return File::get(resource_path('commands/installation/install-nodejs.sh'));
    }

    public function content(): string
    {
        return $this->file();
    }
}
