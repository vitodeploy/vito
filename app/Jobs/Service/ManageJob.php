<?php

namespace App\Jobs\Service;

use App\DTOs\SocketEventDTO;
use App\Enums\ServiceStatus;
use App\Events\SocketEvent;
use App\Http\Resources\ServiceResource;
use App\Models\ServerLog;
use App\Models\Service;
use App\Traits\UniqueQueue;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ManageJob implements ShouldQueue
{
    use Queueable;
    use UniqueQueue;

    public function __construct(
        protected Service $service,
        protected string $action,
        protected ServiceStatus $successStatus,
    ) {}

    public function handle(): void
    {
        $this->run("server-{$this->service->server_id}", function () {
            $succeeded = $this->service->handler()->manage($this->action);
            $this->service->status = $succeeded ? $this->successStatus : ServiceStatus::FAILED;
            $this->service->save();
            $this->broadcastServiceUpdate();
        });
    }

    public function failed(Exception $e): void
    {
        $this->service->status = ServiceStatus::FAILED;
        $this->service->save();
        $this->broadcastServiceUpdate();
        ServerLog::log($this->service->server, "{$this->action}-service-failed", $e->getMessage());
    }

    private function broadcastServiceUpdate(): void
    {
        $this->service->refresh();

        SocketEvent::dispatch(new SocketEventDTO(
            projectId: $this->service->server->project_id,
            type: 'service.updated',
            data: new ServiceResource($this->service),
        ));
    }
}
