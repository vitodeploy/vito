<?php

namespace App\SSHCommands\Database;

use App\SSHCommands\Command;
use Illuminate\Support\Facades\File;

class InstallMariadbCommand extends Command
{
    public function file(): string
    {
        return File::get(resource_path('commands/database/install-mariadb.sh'));
    }

    public function content(): string
    {
        return $this->file();
    }
}
