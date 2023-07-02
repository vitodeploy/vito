<?php

namespace App\Jobs\Installation;

use App\Models\Server;
use App\SSHCommands\InstallCertbotCommand;
use Throwable;

class InstallCertbot extends InstallationJob
{
    protected Server $server;

    public function __construct(Server $server)
    {
        $this->server = $server;
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        $this->server->ssh()->exec(new InstallCertbotCommand(), 'install-certbot');
    }
}
