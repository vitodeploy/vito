<?php

namespace App\Jobs\Server\Security;

use App\DTOs\SocketEventDTO;
use App\Enums\ServiceStatus;
use App\Events\SocketEvent;
use App\Http\Resources\ServiceResource;
use App\Models\ServerLog;
use App\Models\Service;
use App\Services\Fail2ban\Fail2ban;
use App\Traits\UniqueQueue;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ConfigureFail2banJob implements ShouldQueue
{
    use Queueable;
    use UniqueQueue;

    public function __construct(protected Service $service) {}

    public function handle(): void
    {
        $this->run("server-{$this->service->server_id}", function (): void {
            /** @var Fail2ban $handler */
            $handler = $this->service->handler();
            $handler->configure();

            $this->broadcast();
        });
    }

    public function failed(Exception $e): void
    {
        $this->service->status = ServiceStatus::FAILED;
        $this->service->save();

        ServerLog::log($this->service->server, 'configure-fail2ban-failed', $e->getMessage());

        $this->broadcast();
    }

    private function broadcast(): void
    {
        $this->service->refresh();

        SocketEvent::dispatch(new SocketEventDTO(
            projectId: $this->service->server->project_id,
            type: 'service.updated',
            data: new ServiceResource($this->service),
        ));
    }
}
