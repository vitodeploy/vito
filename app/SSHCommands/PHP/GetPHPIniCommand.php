<?php

namespace App\SSHCommands\PHP;

use App\SSHCommands\Command;
use Illuminate\Support\Facades\File;

class GetPHPIniCommand extends Command
{
    public function __construct(protected string $version)
    {
    }

    public function file(): string
    {
        return File::get(resource_path('commands/php/get-php-ini.sh'));
    }

    public function content(): string
    {
        return str($this->file())
            ->replace('__version__', $this->version)
            ->toString();
    }
}
