<?php

namespace App\SSHCommands;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ChangeNginxPHPVersionCommand extends Command
{
    /**
     * @var string
     */
    protected $domain;

    /**
     * @var string
     */
    protected $oldVersion;

    /**
     * @var string
     */
    protected $newVersion;

    /**
     * CreateVHostCommand constructor.
     */
    public function __construct(string $domain, string $oldVersion, string $newVersion)
    {
        $this->domain = $domain;
        $this->oldVersion = $oldVersion;
        $this->newVersion = $newVersion;
    }

    public function file(string $os): string
    {
        return File::get(base_path('system/commands/ubuntu/webserver/nginx/change-php-version.sh'));
    }

    public function content(string $os): string
    {
        $command = Str::replace('__domain__', $this->domain, $this->file($os));
        $command = Str::replace('__old_version__', $this->oldVersion, $command);

        return Str::replace('__new_version__', $this->newVersion, $command);
    }
}
