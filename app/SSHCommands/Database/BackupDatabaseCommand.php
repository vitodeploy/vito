<?php

namespace App\SSHCommands\Database;

use App\SSHCommands\Command;
use Illuminate\Support\Facades\File;

class BackupDatabaseCommand extends Command
{
    public function __construct(protected string $provider, protected string $database, protected string $fileName)
    {
    }

    public function file(): string
    {
        return File::get(resource_path(sprintf('commands/database/%s/backup.sh', $this->provider)));
    }

    public function content(): string
    {
        return str($this->file())
            ->replace('__database__', $this->database)
            ->replace('__file__', $this->fileName)
            ->toString();
    }
}
