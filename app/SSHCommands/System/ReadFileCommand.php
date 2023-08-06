<?php

namespace App\SSHCommands\System;

use App\SSHCommands\Command;
use Illuminate\Support\Facades\File;

class ReadFileCommand extends Command
{
    public function __construct(protected string $path)
    {
    }

    public function file(): string
    {
        return File::get(resource_path('commands/system/read-file.sh'));
    }

    public function content(): string
    {
        return str($this->file())
            ->replace('__path__', $this->path)
            ->toString();
    }
}
