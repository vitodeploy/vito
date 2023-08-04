<?php

namespace App\SSHCommands\SSL;

use App\SSHCommands\Command;
use Illuminate\Support\Facades\File;

class CreateCustomSSLCommand extends Command
{
    public function __construct(
        protected string $path,
        protected string $certificate,
        protected string $pk,
        protected string $certificatePath,
        protected string $pkPath
    ) {
    }

    public function file(): string
    {
        return File::get(resource_path('commands/ssl/create-custom-ssl.sh'));
    }

    public function content(): string
    {
        return str($this->file())
            ->replace('__path__', $this->path)
            ->replace('__certificate__', $this->certificate)
            ->replace('__pk__', $this->pk)
            ->replace('__certificate_path__', $this->certificatePath)
            ->replace('__pk_path__', $this->pkPath)
            ->toString();
    }
}
