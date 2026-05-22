<?php

namespace App\Console\Commands;

use App\WebSocket\EventsHandler;
use App\WebSocket\TerminalHandler;
use App\WebSocket\WebSocketServer;
use Illuminate\Console\Command;
use Psr\Http\Message\RequestInterface;
use React\EventLoop\Loop;
use React\Socket\SocketServer;

class WebSocketServeCommand extends Command
{
    protected $signature = 'ws:serve
        {--host=127.0.0.1 : The host to listen on}
        {--port=8085 : The port to listen on}
        {--max-connections=50 : Maximum concurrent WebSocket connections}';

    protected $description = 'Start the WebSocket server';

    public function handle(): void
    {
        $host = $this->option('host') ?? config('core.ws_host', '127.0.0.1');
        $port = $this->option('port') ?? config('core.ws_port', '8085');
        $maxConnections = (int) $this->option('max-connections');

        if (! config('core.ws_broadcast_secret_set')) {
            $this->warn('WS_BROADCAST_SECRET not set, falling back to APP_KEY. Set a dedicated secret for production.');
        }

        $allowedOrigins = config('core.ws_allowed_origins', []);
        if ($allowedOrigins === []) {
            $appUrl = config('app.url');
            if ($appUrl) {
                $allowedOrigins = [$appUrl];
            }
        }

        $loop = Loop::get();

        $server = new WebSocketServer($loop, $maxConnections, $allowedOrigins);

        $server->route('/ws/terminal', new TerminalHandler($loop));

        $eventsHandler = new EventsHandler;
        $server->route('/ws/events', $eventsHandler);

        $this->registerBroadcastEndpoint($server, $eventsHandler);

        $socket = new SocketServer("{$host}:{$port}", [], $loop);

        $socket->on('connection', [$server, 'handleConnection']);

        $socket->on('error', function (\Throwable $e): void {
            $this->error('Socket error: '.$e->getMessage());
        });

        $this->info("WebSocket server started on {$host}:{$port}");
        $this->info("Max connections: {$maxConnections}");

        $loop->run();
    }

    protected function registerBroadcastEndpoint(WebSocketServer $server, EventsHandler $eventsHandler): void
    {
        $broadcastSecret = config('core.ws_broadcast_secret');

        $server->httpRoute('/ws/broadcast', function (RequestInterface $request) use ($eventsHandler, $broadcastSecret): void {
            $auth = $request->getHeaderLine('Authorization');
            $expectedToken = "Bearer {$broadcastSecret}";
            if (! hash_equals($expectedToken, $auth)) {
                throw new \RuntimeException('Unauthorized');
            }

            $body = json_decode((string) $request->getBody(), true);
            if (! is_array($body) || ! isset($body['project_id'])) {
                throw new \RuntimeException('Invalid payload');
            }

            $projectId = (int) $body['project_id'];
            if ($projectId < 0) {
                throw new \RuntimeException('Invalid project ID');
            }

            if ($projectId === 0) {
                $eventsHandler->broadcastToAll($body);
            } else {
                $eventsHandler->broadcastToProject($projectId, $body);
            }
        });
    }
}
