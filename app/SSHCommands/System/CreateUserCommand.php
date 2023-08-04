<?php

namespace App\SSHCommands\System;

use App\SSHCommands\Command;
use Illuminate\Support\Facades\File;

class CreateUserCommand extends Command
{
    public function __construct(protected string $user, protected string $password, protected string $key)
    {
    }

    public function file(): string
    {
        return File::get(resource_path('commands/system/create-user.sh'));
    }

    public function content(): string
    {
        return str($this->file())
            ->replace('__user__', $this->user)
            ->replace('__key__', $this->key)
            ->replace('__password__', $this->password)
            ->toString();
    }
}
