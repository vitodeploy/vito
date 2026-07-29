<?php

namespace App\Jobs\Service;

use App\Actions\Service\ProbeServices;
use App\Actions\Service\RefreshServices;
use App\DTOs\SocketEventDTO;
use App\Events\SocketEvent;
use App\Models\Server;
use App\Models\ServerLog;
use App\Traits\UniqueQueue;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RefreshServicesJob implements ShouldQueue
{
    use Queueable;
    use UniqueQueue;

    public function __construct(protected Server $server) {}

    public function handle(): void
    {
        $this->run("server-{$this->server->id}", function (): void {
            try {
                app(ProbeServices::class)->probe($this->server);
            } finally {
                RefreshServices::clearFlag($this->server);
            }
        });
    }

    public function failed(Exception $e): void
    {
        RefreshServices::clearFlag($this->server);

        ServerLog::log($this->server, 'refresh-services-failed', $e->getMessage());

        SocketEvent::dispatch(new SocketEventDTO(
            projectId: $this->server->project_id,
            type: 'service.refreshed',
            data: ['server_id' => $this->server->id],
        ));
    }

    protected function lockSeconds(): int
    {
        return ProbeServices::budget($this->server) + 120;
    }
}
