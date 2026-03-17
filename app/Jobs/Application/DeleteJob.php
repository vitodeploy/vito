<?php

namespace App\Jobs\Application;

use App\DTOs\SocketEventDTO;
use App\Enums\ApplicationStatus;
use App\Events\SocketEvent;
use App\Http\Resources\ApplicationResource;
use App\Models\Application;
use App\Models\ServerLog;
use App\Traits\UniqueQueue;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DeleteJob implements ShouldQueue
{
    use Queueable;
    use UniqueQueue;

    public function __construct(protected Application $application) {}

    public function handle(): void
    {
        $this->run("server-{$this->application->server_id}", function () {
            $this->application->type()->uninstall();
            $this->application->delete();

            $this->broadcastDeleted();
        });
    }

    public function failed(Exception $e): void
    {
        $this->application->status = ApplicationStatus::READY;
        $this->application->save();

        ServerLog::log(
            $this->application->server,
            'application-delete-failed',
            $e->getMessage(),
            $this->application,
        );

        $this->broadcastUpdate();
    }

    private function broadcastDeleted(): void
    {
        SocketEvent::dispatch(new SocketEventDTO(
            projectId: $this->application->server->project_id,
            type: 'application.deleted',
            data: ['id' => $this->application->id],
        ));
    }

    private function broadcastUpdate(): void
    {
        $this->application->refresh();

        SocketEvent::dispatch(new SocketEventDTO(
            projectId: $this->application->server->project_id,
            type: 'application.updated',
            data: new ApplicationResource($this->application),
        ));
    }
}
