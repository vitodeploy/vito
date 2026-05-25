<?php

namespace App\Actions\Server;

use App\DTOs\SocketEventDTO;
use App\Events\SocketEvent;
use App\Http\Resources\ServerResource;
use App\Models\Server;

class BroadcastServerUpdate
{
    public function broadcast(Server $server): void
    {
        $server->refresh()->load('latestMetric');

        SocketEvent::dispatch(new SocketEventDTO(
            projectId: $server->project_id,
            type: 'server.updated',
            data: new ServerResource($server),
        ));
    }
}
