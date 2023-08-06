<?php

namespace App\SSHCommands\System;

use App\SSHCommands\Command;
use Illuminate\Support\Facades\File;

class EditFileCommand extends Command
{
    public function __construct(protected string $path, protected string $content)
    {
    }

    public function file(): string
    {
        return File::get(resource_path('commands/system/edit-file.sh'));
    }

    public function content(): string
    {
        return str($this->file())
            ->replace('__path__', $this->path)
            ->replace('__content__', $this->content)
            ->toString();
    }
}
