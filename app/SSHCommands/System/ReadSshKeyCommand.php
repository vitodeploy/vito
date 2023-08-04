<?php

namespace App\SSHCommands\System;

use App\SSHCommands\Command;
use Illuminate\Support\Facades\File;

class ReadSshKeyCommand extends Command
{
    public function __construct(protected string $name)
    {
    }

    public function file(): string
    {
        return File::get(resource_path('commands/system/read-ssh-key.sh'));
    }

    public function content(): string
    {
        return str($this->file())
            ->replace('__name__', $this->name)
            ->toString();
    }
}
