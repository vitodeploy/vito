<?php

namespace App\SSHCommands\Firewall;

use App\SSHCommands\Command;
use Illuminate\Support\Facades\File;

class InstallUfwCommand extends Command
{
    public function file(): string
    {
        return File::get(resource_path('commands/firewall/ufw/install-ufw.sh'));
    }

    public function content(): string
    {
        return $this->file();
    }
}
