<?php

namespace App\Jobs\Installation;

use App\Events\Broadcast;
use App\Models\Server;

class ContinueInstallation extends InstallationJob
{
    protected Server $server;

    protected int $attempts;

    public function __construct(Server $server)
    {
        $this->server = $server;
        $this->attempts = 2;
    }

    public function handle(): void
    {
        if ($this->server->provider()->isRunning()) {
            $this->server->install();
        } else {
            $this->attempts--;
            if ($this->attempts > 0) {
                sleep(120);
                $this->handle();
            } else {
                event(new Broadcast('install-server-failed', $this->server->toArray()));
            }
        }
    }
}
