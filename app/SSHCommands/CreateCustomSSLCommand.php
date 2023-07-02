<?php

namespace App\SSHCommands;

use Illuminate\Support\Facades\File;

class CreateCustomSSLCommand extends Command
{
    protected $path;

    protected $certificate;

    protected $pk;

    protected $certificatePath;

    protected $pkPath;

    public function __construct($path, $certificate, $pk, $certificatePath, $pkPath)
    {
        $this->path = $path;
        $this->certificate = $certificate;
        $this->pk = $pk;
        $this->certificatePath = $certificatePath;
        $this->pkPath = $pkPath;
    }

    public function file(string $os): string
    {
        return File::get(base_path('system/commands/common/create-custom-ssl.sh'));
    }

    public function content(string $os): string
    {
        $content = $this->file($os);
        $content = str_replace('__path__', $this->path, $content);
        $content = str_replace('__certificate__', $this->certificate, $content);
        $content = str_replace('__pk__', $this->pk, $content);
        $content = str_replace('__certificate_path__', $this->certificatePath, $content);

        return str_replace('__pk_path__', $this->pkPath, $content);
    }
}
