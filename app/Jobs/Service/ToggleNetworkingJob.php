<?php

namespace App\Jobs\Service;

use App\DTOs\SocketEventDTO;
use App\Enums\ServiceStatus;
use App\Events\SocketEvent;
use App\Http\Resources\ServiceResource;
use App\Models\ServerLog;
use App\Models\Service;
use App\Services\SupportsNetworking;
use App\Traits\UniqueQueue;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use RuntimeException;

class ToggleNetworkingJob implements ShouldQueue
{
    use Queueable;
    use UniqueQueue;

    public function __construct(
        protected Service $service,
        protected bool $enable,
        protected ServiceStatus $previousStatus,
    ) {}

    public function handle(): void
    {
        $this->run("server-{$this->service->server_id}", function (): void {
            $this->service->status = $this->previousStatus;

            $handler = $this->service->handler();

            if (! $handler instanceof SupportsNetworking) {
                throw new RuntimeException("{$this->service->name} does not support networking.");
            }

            if ($this->enable) {
                $handler->enableNetworking();
            } else {
                $handler->disableNetworking();
            }

            $this->service->jsonUpdate('type_data', 'networking', $this->enable, save: false);
            $this->service->jsonForget('type_data', 'networking_failed', save: false);
            $handler->rememberEffectiveNetworking(
                $this->enable,
                observed: $this->previousStatus === ServiceStatus::READY
            );
            $this->service->save();

            $this->broadcastServiceUpdate();
        });
    }

    public function failed(Exception $e): void
    {
        $this->service->refresh();
        $this->service->jsonUpdate('type_data', 'networking_failed', true, save: false);

        $handler = $this->service->hasHandler() ? $this->service->handler() : null;

        if ($handler instanceof SupportsNetworking) {
            $handler->rememberEffectiveNetworking(null);
        }

        $this->service->status = ServiceStatus::FAILED;
        $this->service->save();
        $this->broadcastServiceUpdate();
        ServerLog::log(
            $this->service->server,
            ($this->enable ? 'enable' : 'disable').'-networking-failed',
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
