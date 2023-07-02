<?php

namespace App\SSHCommands\Storage;

use App\SSHCommands\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class UploadToDropboxCommand extends Command
{
    protected $src;

    protected $dest;

    protected $token;

    public function __construct($src, $dest, $token)
    {
        $this->src = $src;
        $this->dest = $dest;
        $this->token = $token;
    }

    public function file(string $os): string
    {
        return File::get(base_path('system/commands/common/storage/upload-to-dropbox.sh'));
    }

    public function content(string $os): string
    {
        $command = $this->file($os);
        $command = Str::replace('__src__', $this->src, $command);
        $command = Str::replace('__dest__', $this->dest, $command);

        return Str::replace('__token__', $this->token, $command);
    }
}
