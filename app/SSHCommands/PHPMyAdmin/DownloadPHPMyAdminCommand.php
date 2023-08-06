<?php

namespace App\SSHCommands\PHPMyAdmin;

use App\SSHCommands\Command;
use Illuminate\Support\Facades\File;

class DownloadPHPMyAdminCommand extends Command
{
    public function file(): string
    {
        return File::get(resource_path('commands/phpmyadmin/download-phpmyadmin.sh'));
    }

    public function content(): string
    {
        return $this->file();
    }
}
