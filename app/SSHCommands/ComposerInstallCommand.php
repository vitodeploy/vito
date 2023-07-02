<?php

namespace App\SSHCommands;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ComposerInstallCommand extends Command
{
    protected $path;

    /**
     * ComposerInstallCommand constructor.
     */
    public function __construct($path)
    {
        $this->path = $path;
    }

    public function file(string $os): string
    {
        return File::get(base_path('system/commands/common/composer-install.sh'));
    }

    public function content(string $os): string
    {
        return Str::replace('__path__', $this->path, $this->file($os));
    }
}
