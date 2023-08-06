<?php

namespace App\SSHCommands\System;

use App\SSHCommands\Command;
use Illuminate\Support\Facades\File;

class DeleteSshKeyCommand extends Command
{
    public function __construct(protected string $key)
    {
    }

    public function file(): string
    {
        return File::get(resource_path('commands/system/delete-ssh-key.sh'));
    }

    public function content(): string
    {
        return str($this->file())
            ->replace('__key__', str($this->key)->replace('/', '\/'))
            ->toString();
    }
}
