<?php

namespace App\Jobs\Installation;

use App\Models\Server;
use App\SSHCommands\Installation\InstallRequirementsCommand;
use Throwable;

class InstallRequirements extends InstallationJob
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
        $this->server->ssh()->exec(new InstallRequirementsCommand(
            $this->server->creator->email,
            $this->server->creator->name,
        ), 'install-requirements');
    }
}
