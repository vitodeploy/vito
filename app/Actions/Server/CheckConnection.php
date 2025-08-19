<?php

namespace App\Actions\Server;

use App\Enums\ServerStatus;
use App\Facades\Notifier;
use App\Models\Server;
use App\Notifications\ServerDisconnected;
use Throwable;

class CheckConnection
{
    public function check(Server $server, int $retry = 1): Server
    {
        $status = $server->status;
        try {
            $server->ssh()->connect();
            $server->refresh();
            if (in_array($status, [ServerStatus::DISCONNECTED, ServerStatus::UPDATING])) {
                $server->status = ServerStatus::READY;
                $server->save();
            }
        } catch (Throwable) {
            if ($retry > 0) {
                sleep(2);

                return $this->check($server, $retry - 1);
            }
            $server->status = ServerStatus::DISCONNECTED;
            $server->save();
            Notifier::send($server, new ServerDisconnected($server));
        }

        return $server;
    }
}
