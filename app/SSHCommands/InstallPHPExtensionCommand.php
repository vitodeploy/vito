<?php

namespace App\SSHCommands;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class InstallPHPExtensionCommand extends Command
{
    /**
     * @var string
     */
    protected $version;

    protected $name;

    /**
     * InstallPHPCommand constructor.
     */
    public function __construct($version, $name)
    {
        $this->version = $version;
        $this->name = $name;
    }

    public function file(string $os): string
    {
        return File::get(base_path('system/commands/ubuntu/install-php-extension.sh'));
    }

    public function content(string $os): string
    {
        $command = Str::replace('__version__', $this->version, $this->file($os));

        return Str::replace('__name__', $this->name, $command);
    }
}
