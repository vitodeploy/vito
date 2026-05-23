<?php

namespace App\Jobs\Server;

use App\DTOs\SocketEventDTO;
use App\Events\SocketEvent;
use App\Http\Resources\ServerResource;
use App\Models\Server;
use App\Traits\UniqueQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CheckForUpdatesJob implements ShouldQueue
{
    use Queueable;
    use UniqueQueue;

    public int $timeout = 90;

    public function __construct(protected Server $server) {}

    public function handle(): void
    {
        $this->run("server-{$this->server->id}-check-updates", function () {
            $this->server->checkForUpdates();

            SocketEvent::dispatch(new SocketEventDTO(
                projectId: $this->server->project_id,
                type: 'server.updated',
                data: new ServerResource($this->server->refresh()),
            ));
        });
    }
}
