<?php

namespace App\SSHCommands;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class GetPHPIniCommand extends Command
{
    /**
     * @var string
     */
    protected $version;

    public function __construct($version)
    {
        $this->version = $version;
    }

    public function file(string $os): string
    {
        return File::get(base_path('system/commands/ubuntu/get-php-ini.sh'));
    }

    public function content(string $os): string
    {
        return Str::replace('__version__', $this->version, $this->file($os));
    }
}
