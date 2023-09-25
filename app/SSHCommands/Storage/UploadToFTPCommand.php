<?php

namespace App\SSHCommands\Storage;

use App\SSHCommands\Command;
use Illuminate\Support\Facades\File;

class UploadToFTPCommand extends Command
{
    public function __construct(
        protected string $src,
        protected string $dest,
        protected string $host,
        protected string $port,
        protected string $username,
        protected string $password,
        protected bool $ssl,
        protected bool $passive,
    ) {
    }

    public function file(): string
    {
        return File::get(resource_path('commands/storage/upload-to-ftp.sh'));
    }

    public function content(): string
    {
        return str($this->file())
            ->replace('__src__', $this->src)
            ->replace('__dest__', $this->dest)
            ->replace('__host__', $this->host)
            ->replace('__port__', $this->port)
            ->replace('__username__', $this->username)
            ->replace('__password__', $this->password)
            ->replace('__ssl__', $this->ssl ? 's' : '')
            ->replace('__passive__', $this->passive ? '--ftp-pasv' : '')
            ->toString();
    }
}
