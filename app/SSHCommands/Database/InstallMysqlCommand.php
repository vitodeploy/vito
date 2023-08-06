<?php

namespace App\SSHCommands\Database;

use App\SSHCommands\Command;
use Illuminate\Support\Facades\File;

class InstallMysqlCommand extends Command
{
    public function __construct(protected string $version)
    {
    }

    public function file(): string
    {
        if ($this->version == '8.0') {
            return File::get(resource_path('commands/database/install-mysql-8.sh'));
        }

        return File::get(resource_path('commands/database/install-mysql.sh'));
    }

    public function content(): string
    {
        return $this->file();
    }
}
