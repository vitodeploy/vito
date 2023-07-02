<?php

namespace App\SSHCommands;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class UninstallPHPCommand extends Command
{
    /**
     * @var string
     */
    protected $version;

    /**
     * InstallPHPCommand constructor.
     */
    public function __construct($version)
    {
        $this->version = $version;
    }

    public function file(string $os): string
    {
        return File::get(base_path('system/commands/ubuntu/uninstall-php.sh'));
    }

    public function content(string $os): string
    {
        $command = Str::replace('__version__', $this->version, $this->file($os));

        return Str::replace('__user__', config('core.ssh_user'), $command);
    }
}
