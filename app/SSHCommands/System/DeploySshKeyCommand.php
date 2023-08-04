<?php

namespace App\SSHCommands\System;

use App\SSHCommands\Command;
use Illuminate\Support\Facades\File;

class DeploySshKeyCommand extends Command
{
    public function __construct(protected string $key)
    {
    }

    public function file(): string
    {
        return File::get(resource_path('commands/system/deploy-ssh-key.sh'));
    }

    public function content(): string
    {
        return str($this->file())
            ->replace('__key__', addslashes($this->key))
            ->toString();
    }
}
