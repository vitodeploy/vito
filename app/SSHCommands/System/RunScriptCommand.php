<?php

namespace App\SSHCommands\System;

use App\SSHCommands\Command;
use Illuminate\Support\Facades\File;

class RunScriptCommand extends Command
{
    public function __construct(protected string $path, protected string $script)
    {
    }

    public function file(): string
    {
        return File::get(resource_path('commands/system/run-script.sh'));
    }

    public function content(): string
    {
        return str($this->file())
            ->replace('__path__', $this->path)
            ->replace('__script__', $this->script)
            ->toString();
    }
}
