<?php

namespace App\SSHCommands;

use Illuminate\Support\Facades\File;

class RebootCommand extends Command
{
    public function file(string $os): string
    {
        return File::get(base_path('system/commands/ubuntu/reboot.sh'));
    }

    public function content(string $os): string
    {
        return $this->file($os);
    }
}
