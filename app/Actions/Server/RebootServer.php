<?php

namespace App\Actions\Server;

use App\Enums\ServerStatus;
use App\Models\Server;
use Throwable;

class RebootServer
{
    public function reboot(Server $server): Server
    {
        try {
            $server->os()->reboot();
            $server->status = ServerStatus::DISCONNECTED;
            $server->save();
        } catch (Throwable) {
            $server = $server->checkConnection();
        }

        return $server;
    }
}
