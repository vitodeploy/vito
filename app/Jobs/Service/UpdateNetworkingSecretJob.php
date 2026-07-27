<?php

namespace App\Jobs\Service;

use App\DTOs\SocketEventDTO;
use App\Enums\ServiceStatus;
use App\Events\SocketEvent;
use App\Http\Resources\ServiceResource;
use App\Models\ServerLog;
use App\Models\Service;
use App\Services\SupportsNetworkingSecret;
use App\Traits\UniqueQueue;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use RuntimeException;

class UpdateNetworkingSecretJob implements ShouldQueue
{
    use Queueable;
    use UniqueQueue;

    public function __construct(
        protected Service $service,
        protected bool $remove,
        protected ServiceStatus $previousStatus,
    ) {}

    public function handle(): void
    {
        $this->run("server-{$this->service->server_id}", function (): void {
            $this->service->status = $this->previousStatus;

            $handler = $this->service->handler();

            if (! $handler instanceof SupportsNetworkingSecret) {
                throw new RuntimeException("{$this->service->name} does not use a networking password.");
            }

            $secret = $this->remove ? null : $handler->generateNetworkingSecret();

            $handler->writeNetworkingSecret($secret);

            $this->service->secret = $secret;
            $this->service->save();

            $this->broadcastServiceUpdate();
        });
    }

    public function failed(Exception $e): void
    {
        $this->service->refresh();
        $this->service->status = ServiceStatus::FAILED;
        $this->service->save();
        $this->broadcastServiceUpdate();
        ServerLog::log(
            $this->service->server,
            ($this->remove ? 'remove' : 'regenerate').'-networking-secret-failed',
            $e->getMessage()
        );
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
