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

        $loop = Loop::get();

        $server = new WebSocketServer($loop, $maxConnections);

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
        $appKey = config('app.key');

        $server->httpRoute('/ws/broadcast', function (RequestInterface $request) use ($eventsHandler, $appKey): void {
            $auth = $request->getHeaderLine('Authorization');
            if ($auth !== "Bearer {$appKey}") {
                throw new \RuntimeException('Unauthorized');
            }

            $body = json_decode((string) $request->getBody(), true);
            if (! is_array($body) || ! isset($body['project_id'])) {
                throw new \RuntimeException('Invalid payload');
            }

            $projectId = (int) $body['project_id'];
            if ($projectId <= 0) {
                throw new \RuntimeException('Invalid project ID');
            }

            $eventsHandler->broadcastToProject($projectId, $body);
        });
    }
}
