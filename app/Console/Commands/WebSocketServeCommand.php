<?php

namespace App\Console\Commands;

use App\WebSocket\TerminalHandler;
use App\WebSocket\WebSocketServer;
use Illuminate\Console\Command;
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

        $socket = new SocketServer("{$host}:{$port}", [], $loop);

        $socket->on('connection', [$server, 'handleConnection']);

        $socket->on('error', function (\Throwable $e): void {
            $this->error('Socket error: '.$e->getMessage());
        });

        $this->info("WebSocket server started on {$host}:{$port}");
        $this->info("Max connections: {$maxConnections}");

        $loop->run();
    }
}
