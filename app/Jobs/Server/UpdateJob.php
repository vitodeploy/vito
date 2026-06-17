<?php

namespace App\Jobs\Server;

use App\DTOs\SocketEventDTO;
use App\Events\SocketEvent;
use App\Facades\Notifier;
use App\Http\Resources\ServerResource;
use App\Models\Server;
use App\Models\ServerLog;
use App\Notifications\ServerAutoUpdateCompleted;
use App\Notifications\ServerUpdateFailed;
use App\Traits\UniqueQueue;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class UpdateJob implements ShouldQueue
{
    use Queueable;
    use UniqueQueue;

    public function __construct(protected Server $server, protected bool $notify = false) {}

    public function handle(): void
    {
        $this->run("server-{$this->server->id}", function () {
            $result = $this->server->os()->upgrade();
            $this->server->checkConnection();
            $this->server->checkForUpdates();
            $kernelUpdatesRemaining = $this->server->kernel_updates;
            $this->broadcastServerUpdate();

            if ($this->notify && ($result['upgraded'] > 0 || $result['reboot_required'])) {
                Notifier::send($this->server, new ServerAutoUpdateCompleted(
                    $this->server,
                    $result['upgraded'],
                    $kernelUpdatesRemaining,
                    $result['reboot_required'],
                ));
            }
        });
    }

    public function failed(Exception $e): void
    {
        Notifier::send($this->server, new ServerUpdateFailed($this->server));
        $this->server->checkConnection();
        $this->broadcastServerUpdate();

        ServerLog::log(
            $this->server,
            'update-server-failed',
            $e->getMessage()
        );
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
