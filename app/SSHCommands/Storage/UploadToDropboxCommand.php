<?php

namespace App\SSHCommands\Storage;

use App\SSHCommands\Command;
use Illuminate\Support\Facades\File;

class UploadToDropboxCommand extends Command
{
    public function __construct(protected string $src, protected string $dest, protected string $token)
    {
    }

    public function file(): string
    {
        return File::get(resource_path('commands/storage/upload-to-dropbox.sh'));
    }

    public function content(): string
    {
        return str($this->file())
            ->replace('__src__', $this->src)
            ->replace('__dest__', $this->dest)
            ->replace('__token__', $this->token)
            ->toString();
    }
}
