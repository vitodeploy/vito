<?php

namespace App\SSHCommands\Database;

use App\SSHCommands\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class DeleteUserCommand extends Command
{
    /**
     * @var string
     */
    protected $provider;

    /**
     * @var string
     */
    protected $username;

    /**
     * @var string
     */
    protected $host;

    public function __construct($provider, $username, $host)
    {
        $this->provider = $provider;
        $this->username = $username;
        $this->host = $host;
    }

    public function file(string $os): string
    {
        return File::get(base_path('system/commands/database/'.$this->provider.'/delete-user.sh'));
    }

    public function content(string $os): string
    {
        $command = Str::replace('__username__', $this->username, $this->file($os));

        return Str::replace('__host__', $this->host, $command);
    }
}
