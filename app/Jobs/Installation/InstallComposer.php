<?php

namespace App\Jobs\Installation;

use App\Models\Server;
use App\SSHCommands\PHP\InstallComposerCommand;
use Throwable;

class InstallComposer extends InstallationJob
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
        $this->server->ssh()->exec(new InstallComposerCommand(), 'install-composer');
    }
}
