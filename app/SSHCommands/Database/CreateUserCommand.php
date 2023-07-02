<?php

namespace App\SSHCommands\Database;

use App\SSHCommands\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CreateUserCommand extends Command
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
    protected $password;

    /**
     * @var string
     */
    protected $host;

    public function __construct($provider, $username, $password, $host)
    {
        $this->provider = $provider;
        $this->username = $username;
        $this->password = $password;
        $this->host = $host;
    }

    public function file(string $os): string
    {
        return File::get(base_path('system/commands/database/'.$this->provider.'/create-user.sh'));
    }

    public function content(string $os): string
    {
        $command = Str::replace('__username__', $this->username, $this->file($os));
        $command = Str::replace('__password__', $this->password, $command);

        return Str::replace('__host__', $this->host, $command);
    }
}
