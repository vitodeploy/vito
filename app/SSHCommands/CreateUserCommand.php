<?php

namespace App\SSHCommands;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CreateUserCommand extends Command
{
    protected $user;

    protected $password;

    protected $key;

    /**
     * CreateUserCommand constructor.
     */
    public function __construct($user, $password, $key)
    {
        $this->user = $user;
        $this->password = $password;
        $this->key = $key;
    }

    public function file(string $os): string
    {
        return File::get(base_path('system/commands/ubuntu/create-user.sh'));
    }

    public function content(string $os): string
    {
        $command = $this->file($os);
        $command = Str::replace('__user__', $this->user, $command);
        $command = Str::replace('__key__', $this->key, $command);

        return Str::replace('__password__', $this->password, $command);
    }
}
