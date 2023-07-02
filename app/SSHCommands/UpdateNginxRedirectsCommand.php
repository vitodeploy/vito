<?php

namespace App\SSHCommands;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class UpdateNginxRedirectsCommand extends Command
{
    /**
     * @var string
     */
    protected $domain;

    /**
     * @var string
     */
    protected $redirects;

    /**
     * CreateVHostCommand constructor.
     */
    public function __construct(
        string $domain,
        string $redirects
    ) {
        $this->domain = $domain;
        $this->redirects = $redirects;
    }

    public function file(string $os): string
    {
        return File::get(base_path('system/commands/ubuntu/webserver/nginx/update-redirects.sh'));
    }

    public function content(string $os): string
    {
        info($this->redirects);
        $command = Str::replace('__redirects__', addslashes($this->redirects), $this->file($os));

        return Str::replace('__domain__', $this->domain, $command);
    }
}
