<?php

namespace App\SSHCommands\Database;

use App\SSHCommands\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class RestoreDatabaseCommand extends Command
{
    protected $provider;

    protected $database;

    protected $fileName;

    public function __construct($provider, $database, $fileName)
    {
        $this->provider = $provider;
        $this->database = $database;
        $this->fileName = $fileName;
    }

    public function file(string $os): string
    {
        return File::get(base_path('system/commands/database/'.$this->provider.'/restore.sh'));
    }

    public function content(string $os): string
    {
        $command = $this->file($os);
        $command = Str::replace('__database__', $this->database, $command);

        return Str::replace('__file__', $this->fileName, $command);
    }
}
