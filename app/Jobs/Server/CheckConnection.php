<?php

namespace App\Jobs\Server;

use App\Events\Broadcast;
use App\Facades\Notifier;
use App\Jobs\Job;
use App\Models\Server;
use App\Notifications\ServerDisconnected;
use Throwable;

class CheckConnection extends Job
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
        $status = $this->server->status;
        $this->server->ssh()->connect();
        $this->server->refresh();
        if ($status == 'disconnected') {
            $this->server->status = 'ready';
            $this->server->save();
        }
        event(
            new Broadcast('server-status-finished', [
                'server' => $this->server,
            ])
        );
    }

    public function failed(): void
    {
        $this->server->status = 'disconnected';
        $this->server->save();
        Notifier::send($this->server, new ServerDisconnected($this->server));
        event(
            new Broadcast('server-status-failed', [
                'server' => $this->server,
            ])
        );
    }
}
