<?php

namespace App\Jobs\PHP;

use App\DTOs\SocketEventDTO;
use App\Events\SocketEvent;
use App\Http\Resources\ServiceResource;
use App\Models\ServerLog;
use App\Models\Service;
use App\Services\PHP\PHP;
use App\Traits\UniqueQueue;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class InstallExtensionJob implements ShouldQueue
{
    use Queueable;
    use UniqueQueue;

    public function __construct(
        protected Service $service,
        protected string $extension
    ) {}

    public function handle(): void
    {
        $this->run("server-{$this->service->server_id}", function () {
            /** @var PHP $handler */
            $handler = $this->service->handler();
            $handler->installExtension($this->extension);
            $this->broadcastServiceUpdate();
        });
    }

    public function failed(Exception $e): void
    {
        $this->service->refresh();
        $typeData = $this->service->type_data;
        $typeData['extensions'] = array_values(array_diff($typeData['extensions'], [$this->extension]));
        $this->service->type_data = $typeData;
        $this->service->save();
        $this->broadcastServiceUpdate();

        ServerLog::log(
            $this->service->server,
            'install-php-extension-failed',
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
