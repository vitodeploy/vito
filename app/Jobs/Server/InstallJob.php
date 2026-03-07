<?php

namespace App\Jobs\Server;

use App\Actions\Server\InstallServer;
use App\DTOs\SocketEventDTO;
use App\Enums\ServerStatus;
use App\Events\SocketEvent;
use App\Facades\Notifier;
use App\Http\Resources\ServerResource;
use App\Models\Server;
use App\Models\ServerLog;
use App\Notifications\ServerInstallationFailed;
use App\Traits\UniqueQueue;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class InstallJob implements ShouldQueue
{
    use Queueable;
    use UniqueQueue;

    public function __construct(protected Server $server) {}

    public function handle(): void
    {
        $this->run("server-{$this->server->id}", function () {
            app(InstallServer::class)->run($this->server);
            $this->broadcastServerUpdate();
        });
    }

    public function failed(Exception $e): void
    {
        $this->server->update([
            'status' => ServerStatus::INSTALLATION_FAILED,
        ]);
        $this->broadcastServerUpdate();
        Notifier::send($this->server, new ServerInstallationFailed($this->server));
        ServerLog::log($this->server, 'server-installation-failed', $e->getMessage());
    }

    private function broadcastServerUpdate(): void
    {
        $this->server->refresh();

        SocketEvent::dispatch(new SocketEventDTO(
            projectId: $this->server->project_id,
            type: 'server.updated',
            data: new ServerResource($this->server),
        ));
    }
}
