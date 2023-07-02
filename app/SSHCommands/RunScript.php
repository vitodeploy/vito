<?php

namespace App\SSHCommands;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class RunScript extends Command
{
    protected $path;

    protected $script;

    public function __construct($path, $script)
    {
        $this->path = $path;
        $this->script = $script;
    }

    public function file(string $os): string
    {
        return File::get(base_path('system/commands/common/run-script.sh'));
    }

    public function content(string $os): string
    {
        $command = Str::replace('__path__', $this->path, $this->file($os));

        return Str::replace('__script__', make_bash_script($this->script), $command);
    }
}
