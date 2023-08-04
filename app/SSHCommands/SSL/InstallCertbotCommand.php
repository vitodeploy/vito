<?php

namespace App\SSHCommands\SSL;

use App\SSHCommands\Command;
use Illuminate\Support\Facades\File;

class InstallCertbotCommand extends Command
{
    public function file(): string
    {
        return File::get(resource_path('commands/ssl/install-certbot.sh'));
    }

    public function content(): string
    {
        return $this->file();
    }
}
