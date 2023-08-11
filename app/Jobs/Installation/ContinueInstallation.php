<?php

namespace App\Jobs\Installation;

use App\Events\Broadcast;
use App\Models\Server;

class ContinueInstallation extends InstallationJob
{
    protected Server $server;

    protected int $attempts;

    public function __construct(Server $server, int $attempts = 0)
    {
        $this->server = $server;
        $this->attempts = $attempts;
    }

    public function handle(): void
    {
        if ($this->server->provider()->isRunning()) {
            $this->server->install();
            return;
        }

        if ($this->attempts >= 2) {
            $this->server->update([
                'status' => 'installation_failed',
            ]);
            event(
                new Broadcast('install-server-failed', [
                    'server' => $this->server,
                ])
            );
            return;
        }

        dispatch(new self($this->server, $this->attempts++))->delay(now()->addMinute());
    }
}
