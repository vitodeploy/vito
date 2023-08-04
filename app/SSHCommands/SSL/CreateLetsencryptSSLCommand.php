<?php

namespace App\SSHCommands\SSL;

use App\SSHCommands\Command;
use Illuminate\Support\Facades\File;

class CreateLetsencryptSSLCommand extends Command
{
    public function __construct(protected string $email, protected string $domain, protected string $webDirectory)
    {
    }

    public function file(): string
    {
        return File::get(resource_path('commands/ssl/create-letsencrypt-ssl.sh'));
    }

    public function content(): string
    {
        return str($this->file())
            ->replace('__email__', $this->email)
            ->replace('__web_directory__', $this->webDirectory)
            ->replace('__domain__', $this->domain)
            ->toString();
    }
}
