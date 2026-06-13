<?php

namespace App\Actions\Server;

use App\Enums\ServerStatus;
use App\Facades\Notifier;
use App\Models\Server;
use App\Notifications\ServerConnected;
use App\Notifications\ServerDisconnected;
use Illuminate\Support\Sleep;
use Throwable;

class CheckConnection
{
    public function check(Server $server, int $retry = 2): Server
    {
        $status = $server->refresh()->status;
        try {
            $server->ssh()->connect();
            $server->refresh();
            if (in_array($status, [ServerStatus::DISCONNECTED, ServerStatus::UPDATING])) {
                $server->status = ServerStatus::READY;
                $server->save();
                app(BroadcastServerUpdate::class)->broadcast($server);
                if ($status === ServerStatus::DISCONNECTED) {
                    Notifier::send($server, new ServerConnected($server));
                }
            }
        } catch (Throwable) {
            if ($retry > 0) {
                Sleep::sleep(3);

                return $this->check($server, $retry - 1);
            }
            if ($status !== ServerStatus::DISCONNECTED) {
                $server->status = ServerStatus::DISCONNECTED;
                $server->save();
                app(BroadcastServerUpdate::class)->broadcast($server);
                Notifier::send($server, new ServerDisconnected($server));
            }
        }

        return $server;
    }
}
