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
use Illuminate\Support\Facades\Log;

class InstallJob implements ShouldQueue
{
    use Queueable;
    use UniqueQueue;

    public function __construct(protected Service $service) {}

    public function handle(): void
    {
        $succeeded = false;
        $this->run("server-{$this->service->server_id}", function () use (&$succeeded) {
            Log::info("Installing service ID {$this->service->id} on server ID {$this->service->server_id}");

            $this->service->newLog();

            $handler = $this->service->handler();

            $handler->install();
            $this->service->status = ServiceStatus::READY;
            $this->service->installed_version = $handler->version();

            $this->service->save();
            $this->broadcastServiceUpdate('service.updated');
            Log::info("Service ID {$this->service->id} installed successfully");
            $succeeded = true;
        });

        if ($succeeded) {
            UpdateVitoAgentConfigJob::dispatchFor($this->service);
        }
    }

    public function failed(Exception $e): void
    {
        $this->service->status = ServiceStatus::INSTALLATION_FAILED;
        $this->service->save();
        $this->broadcastServiceUpdate('service.updated');

        ServerLog::log(
            $this->service->server,
            'service-installation-failed',
            $e->getMessage()
        );
    }

    private function broadcastServiceUpdate(string $type): void
    {
        $this->service->refresh();

        SocketEvent::dispatch(new SocketEventDTO(
            projectId: $this->service->server->project_id,
            type: $type,
            data: new ServiceResource($this->service),
        ));
    }
}
