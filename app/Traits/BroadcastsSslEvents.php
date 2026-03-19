<?php

namespace App\Traits;

use App\DTOs\SocketEventDTO;
use App\Enums\SslStatus;
use App\Events\SocketEvent;
use App\Http\Resources\SslResource;
use App\Models\ServerLog;
use App\Models\Ssl;

trait BroadcastsSslEvents
{
    /**
     * Broadcast an SSL update event via WebSocket.
     */
    protected function broadcastSslEvent(Ssl $ssl, int $projectId, string $type = 'ssl.updated'): void
    {
        $ssl->refresh();

        SocketEvent::dispatch(new SocketEventDTO(
            projectId: $projectId,
            type: $type,
            data: new SslResource($ssl),
        ));
    }

    /**
     * Handle a failed SSL job: set status to FAILED, broadcast, and log.
     */
    protected function handleSslFailure(Ssl $ssl, \Throwable $e, int $projectId, string $logKey): void
    {
        $ssl->status = SslStatus::FAILED;
        $ssl->save();

        $this->broadcastSslEvent($ssl, $projectId);

        ServerLog::log(
            $this->server,
            $logKey,
            $e->getMessage(),
        );
    }
}
