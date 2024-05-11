<?php

namespace App\Actions\Server;

use App\Enums\ServerStatus;
use App\Facades\Notifier;
use App\Models\Server;
use App\Notifications\ServerUpdateFailed;

class Update
{
    public function update(Server $server): void
    {
        $server->status = ServerStatus::UPDATING;
        $server->save();
        dispatch(function () use ($server) {
            $server->os()->upgrade();
            $server->checkConnection();
            $server->checkForUpdates();
        })->catch(function () use ($server) {
            Notifier::send($server, new ServerUpdateFailed($server));
            $server->checkConnection();
        })->onConnection('ssh');
    }
}
