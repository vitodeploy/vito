<?php

namespace App\SSHCommands;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CreateLetsencryptSSLCommand extends Command
{
    protected $email;

    protected $domain;

    protected $webDirectory;

    public function __construct($email, $domain, $webDirectory)
    {
        $this->email = $email;
        $this->domain = $domain;
        $this->webDirectory = $webDirectory;
    }

    public function file(string $os): string
    {
        return File::get(base_path('system/commands/common/create-letsencrypt-ssl.sh'));
    }

    public function content(string $os): string
    {
        $command = Str::replace('__email__', $this->email, $this->file($os));
        $command = Str::replace('__web_directory__', $this->webDirectory, $command);

        return Str::replace('__domain__', $this->domain, $command);
    }
}
