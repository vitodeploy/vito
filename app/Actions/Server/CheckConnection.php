<?php

namespace App\Actions\Server;

use App\Facades\Notifier;
use App\Models\Server;
use App\Notifications\ServerDisconnected;
use Throwable;

class CheckConnection
{
    public function check(Server $server): Server
    {
        $status = $server->status;
        try {
            $server->ssh()->connect();
            $server->refresh();
            if ($status == 'disconnected') {
                $server->status = 'ready';
                $server->save();
            }
        } catch (Throwable) {
            $server->status = 'disconnected';
            $server->save();
            Notifier::send($server, new ServerDisconnected($server));
        }

        return $server;
    }
}
