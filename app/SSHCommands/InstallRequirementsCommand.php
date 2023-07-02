<?php

namespace App\SSHCommands;

use Illuminate\Support\Facades\File;

class InstallRequirementsCommand extends Command
{
    protected $email;

    protected $name;

    public function __construct($email, $name)
    {
        $this->email = $email;
        $this->name = $name;
    }

    public function file(string $os): string
    {
        return File::get(base_path('system/commands/ubuntu/install-requirements.sh'));
    }

    public function content(string $os): string
    {
        return $this->file($os);
    }
}
