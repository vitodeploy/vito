<?php

namespace App\SSHCommands;

use Illuminate\Support\Facades\File;

class InstallMysqlCommand extends Command
{
    protected $version;

    public function __construct($version)
    {
        $this->version = $version;
    }

    public function file(string $os): string
    {
        if ($this->version == '8.0') {
            return File::get(base_path('system/commands/ubuntu/install-mysql-8.sh'));
        }

        return File::get(base_path('system/commands/ubuntu/install-mysql.sh'));
    }

    public function content(string $os): string
    {
        return $this->file($os);
    }
}
