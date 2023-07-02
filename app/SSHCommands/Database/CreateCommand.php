<?php

namespace App\SSHCommands\Database;

use App\SSHCommands\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CreateCommand extends Command
{
    /**
     * @var string
     */
    protected $provider;

    /**
     * @var string
     */
    protected $name;

    public function __construct($provider, $name)
    {
        $this->provider = $provider;
        $this->name = $name;
    }

    public function file(string $os): string
    {
        return File::get(base_path('system/commands/database/'.$this->provider.'/create.sh'));
    }

    public function content(string $os): string
    {
        return Str::replace('__name__', $this->name, $this->file($os));
    }
}
