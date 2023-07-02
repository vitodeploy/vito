<?php

namespace App\SSHCommands;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class EditFileCommand extends Command
{
    protected $path;

    protected $content;

    public function __construct($path, $content)
    {
        $this->path = $path;
        $this->content = $content;
    }

    public function file(string $os): string
    {
        return File::get(base_path('system/commands/common/edit-file.sh'));
    }

    public function content(string $os): string
    {
        $command = Str::replace('__path__', $this->path, $this->file($os));

        return Str::replace('__content__', addslashes($this->content), $command);
    }
}
