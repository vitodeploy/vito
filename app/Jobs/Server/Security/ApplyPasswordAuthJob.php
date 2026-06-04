<?php

namespace App\Jobs\Server\Security;

use App\DTOs\SocketEventDTO;
use App\Enums\SecurityControlStatus;
use App\Events\SocketEvent;
use App\Models\Server;
use App\Models\ServerLog;
use App\Traits\UniqueQueue;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ApplyPasswordAuthJob implements ShouldQueue
{
    use Queueable;
    use UniqueQueue;

    public function __construct(protected Server $server, protected bool $enabled) {}

    public function handle(): void
    {
        $this->run("server-{$this->server->id}", function (): void {
            $this->server->security()->setPasswordAuth($this->enabled);

            $detected = $this->server->security()->passwordAuthEnabled();

            $this->writeState([
                'enabled' => $this->enabled,
                'detected' => $detected,
                'status' => SecurityControlStatus::READY->value,
            ]);

            $this->broadcast();
        });
    }

    public function failed(Exception $e): void
    {
        $this->writeState([
            'status' => SecurityControlStatus::FAILED->value,
        ]);

        ServerLog::log($this->server, ($this->enabled ? 'enable' : 'disable').'-password-auth-failed', $e->getMessage());

        $this->broadcast();
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function writeState(array $values): void
    {
        $this->server->refresh();
        $security = $this->server->feature_data['security'] ?? [];
        $security['password_authentication'] = array_merge($security['password_authentication'] ?? [], $values);
        $this->server->jsonUpdate('feature_data', 'security', $security);
    }

    private function broadcast(): void
    {
        SocketEvent::dispatch(new SocketEventDTO(
            projectId: $this->server->project_id,
            type: 'security.updated',
            data: ['server_id' => $this->server->id],
        ));
    }
}
