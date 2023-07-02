<?php

namespace App\SSHCommands;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class DeleteNginxSiteCommand extends Command
{
    /**
     * @var string
     */
    protected $domain;

    /**
     * @var string
     */
    protected $path;

    /**
     * CloneRepositoryCommand constructor.
     */
    public function __construct($domain, $path)
    {
        $this->domain = $domain;
        $this->path = $path;
    }

    public function file(string $os): string
    {
        return File::get(base_path('system/commands/ubuntu/webserver/nginx/delete-site.sh'));
    }

    public function content(string $os): string
    {
        $command = Str::replace('__domain__', $this->domain, $this->file($os));

        return Str::replace('__path__', $this->path, $command);
    }
}
