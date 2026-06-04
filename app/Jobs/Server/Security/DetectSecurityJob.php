<?php

namespace App\Jobs\Server\Security;

use App\DTOs\SocketEventDTO;
use App\Events\SocketEvent;
use App\Models\Server;
use App\Models\ServerLog;
use App\Traits\UniqueQueue;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DetectSecurityJob implements ShouldQueue
{
    use Queueable;
    use UniqueQueue;

    public int $timeout = 90;

    public function __construct(protected Server $server)
    {
        $this->onQueue('ssh');
    }

    public function handle(): void
    {
        $this->run("server-{$this->server->id}-detect-security", function (): void {
            $passwordAuth = $this->server->security()->passwordAuthEnabled();
            $rootLogin = $this->server->security()->rootLoginEnabled();

            $this->server->refresh();
            $security = $this->server->feature_data['security'] ?? [];
            $security['password_authentication'] = array_merge(
                $security['password_authentication'] ?? [],
                ['detected' => $passwordAuth]
            );
            $security['root_login'] = array_merge(
                $security['root_login'] ?? [],
                ['detected' => $rootLogin]
            );
            $this->server->jsonUpdate('feature_data', 'security', $security);

            SocketEvent::dispatch(new SocketEventDTO(
                projectId: $this->server->project_id,
                type: 'security.updated',
                data: ['server_id' => $this->server->id],
            ));
        });
    }

    public function failed(Exception $e): void
    {
        ServerLog::log($this->server, 'detect-security-state-failed', $e->getMessage());

        SocketEvent::dispatch(new SocketEventDTO(
            projectId: $this->server->project_id,
            type: 'security.updated',
            data: ['server_id' => $this->server->id],
        ));
    }
}
