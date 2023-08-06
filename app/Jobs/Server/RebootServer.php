<?php

namespace App\Jobs\Server;

use App\Events\Broadcast;
use App\Jobs\Job;
use App\Models\Server;
use App\SSHCommands\System\RebootCommand;
use Throwable;

class RebootServer extends Job
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
        $this->server->ssh()->exec(new RebootCommand(), 'reboot');
        event(
            new Broadcast('reboot-server-finished', [
                'message' => __('The server is being rebooted. It can take several minutes to boot up'),
                'id' => $this->server->id,
            ])
        );
    }

    public function failed(): void
    {
        $this->server->status = 'ready';
        $this->server->save();
        event(
            new Broadcast('reboot-server-failed', [
                'message' => __('Failed to reboot the server'),
                'server' => $this->server,
            ])
        );
    }
}
