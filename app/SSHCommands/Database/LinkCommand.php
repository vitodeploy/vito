<?php

namespace App\SSHCommands\Database;

use App\SSHCommands\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class LinkCommand extends Command
{
    /**
     * @var string
     */
    protected $provider;

    /**
     * @var string
     */
    protected $username;

    protected $host;

    /**
     * @var string
     */
    protected $database;

    public function __construct($provider, $username, $host, $database)
    {
        $this->provider = $provider;
        $this->username = $username;
        $this->host = $host;
        $this->database = $database;
    }

    public function file(string $os): string
    {
        return File::get(base_path('system/commands/database/'.$this->provider.'/link.sh'));
    }

    public function content(string $os): string
    {
        $command = Str::replace('__username__', $this->username, $this->file($os));
        $command = Str::replace('__host__', $this->host, $command);

        return Str::replace('__database__', $this->database, $command);
    }
}
