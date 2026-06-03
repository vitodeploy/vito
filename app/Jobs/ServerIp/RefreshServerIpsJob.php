<?php

namespace App\Jobs\ServerIp;

use App\Actions\ServerIp\RefreshServerIps;
use App\DTOs\SocketEventDTO;
use App\Events\SocketEvent;
use App\Models\Server;
use App\Models\ServerLog;
use App\Traits\UniqueQueue;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RefreshServerIpsJob implements ShouldQueue
{
    use Queueable;
    use UniqueQueue;

    public function __construct(protected Server $server) {}

    public function handle(): void
    {
        $this->run("server-{$this->server->id}", function (): void {
            app(RefreshServerIps::class)->handle($this->server);

            SocketEvent::dispatch(new SocketEventDTO(
                projectId: $this->server->project_id,
                type: 'server-ip.updated',
                data: ['server_id' => $this->server->id],
            ));
        });
    }

    public function failed(Exception $e): void
    {
        ServerLog::log($this->server, 'refresh-server-ips-failed', $e->getMessage());
    }
}
