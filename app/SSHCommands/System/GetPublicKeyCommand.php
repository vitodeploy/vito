<?php

namespace App\SSHCommands\System;

use App\SSHCommands\Command;
use Illuminate\Support\Facades\File;

class GetPublicKeyCommand extends Command
{
    public function file(): string
    {
        return File::get(resource_path('commands/system/get-public-key.sh'));
    }

    public function content(): string
    {
        return $this->file();
    }
}
