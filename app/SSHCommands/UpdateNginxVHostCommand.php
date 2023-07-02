<?php

namespace App\SSHCommands;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class UpdateNginxVHostCommand extends Command
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
     * @var string
     */
    protected $vhost;

    /**
     * CreateVHostCommand constructor.
     */
    public function __construct(
        string $domain,
        string $path,
        string $vhost
    ) {
        $this->domain = $domain;
        $this->path = $path;
        $this->vhost = $vhost;
    }

    public function file(string $os): string
    {
        return File::get(base_path('system/commands/ubuntu/webserver/nginx/update-vhost.sh'));
    }

    public function content(string $os): string
    {
        $command = Str::replace('__path__', $this->path, $this->file($os));
        $command = Str::replace('__domain__', $this->domain, $command);

        return Str::replace('__vhost__', $this->vhost, $command);
    }
}
