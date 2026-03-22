<?php

namespace App\Listeners;

use App\Events\SocketEvent;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SocketEventListener
{
    public function handle(SocketEvent $event): void
    {
        try {
            $host = config('core.ws_host', '127.0.0.1');
            $port = config('core.ws_port', '8085');

            Http::withToken(config('core.ws_broadcast_secret'))
                ->connectTimeout(1)
                ->timeout(1)
                ->post("http://{$host}:{$port}/ws/broadcast", $event->data->toArray());
        } catch (\Throwable $e) {
            Log::error('Failed to broadcast socket event', [
                'error' => $e->getMessage(),
                'project_id' => $event->data->projectId,
            ]);
        }
    }
}
