<?php

namespace App\SSHCommands\System;

use App\SSHCommands\Command;
use Illuminate\Support\Facades\File;

class UpgradeCommand extends Command
{
    public function file(): string
    {
        return File::get(resource_path('commands/system/upgrade.sh'));
    }

    public function content(): string
    {
        return $this->file();
    }
}
