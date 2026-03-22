<?php

namespace App\WebSocket;

use GuzzleHttp\Psr7\HttpFactory;
use Illuminate\Support\Facades\Log;
use Ratchet\RFC6455\Handshake\RequestVerifier;
use Ratchet\RFC6455\Handshake\ServerNegotiator;
use Ratchet\RFC6455\Messaging\CloseFrameChecker;
use Ratchet\RFC6455\Messaging\Frame;
use Ratchet\RFC6455\Messaging\MessageBuffer;
use React\EventLoop\LoopInterface;
use React\Socket\ConnectionInterface;

class WebSocketServer
{
    protected const MAX_HANDSHAKE_BUFFER = 8192;

    protected const PING_INTERVAL = 30;

    protected ServerNegotiator $negotiator;

    /**
     * @var array<string, array{handler: WebSocketHandler, buffer: MessageBuffer, connection: WebSocketConnection}>
     */
    protected array $connections = [];

    /**
     * @var array<string, WebSocketHandler>
     */
    protected array $handlers = [];

    protected int $maxConnections;

    public function __construct(
        protected LoopInterface $loop,
        int $maxConnections = 50,
        protected array $allowedOrigins = [],
    ) {
        $this->negotiator = new ServerNegotiator(
            new RequestVerifier,
            new HttpFactory,
        );
        $this->maxConnections = $maxConnections;

        $this->loop->addPeriodicTimer(self::PING_INTERVAL, function (): void {
            $this->pingConnections();
        });
    }

    public function route(string $path, WebSocketHandler $handler): void
    {
        $this->handlers[$path] = $handler;
    }

    public function handleConnection(ConnectionInterface $conn): void
    {
        $totalConnections = count($this->connections);
        if ($totalConnections >= $this->maxConnections) {
            $conn->close();

            return;
        }

        $connId = spl_object_hash($conn);
        $httpBuffer = '';

        $conn->on('data', function (string $data) use ($conn, $connId, &$httpBuffer): void {
            if (! isset($this->connections[$connId])) {
                $httpBuffer .= $data;
                if (strlen($httpBuffer) > self::MAX_HANDSHAKE_BUFFER) {
                    $conn->close();

                    return;
                }
                $this->handleHandshake($conn, $connId, $httpBuffer);

                return;
            }

            $this->connections[$connId]['buffer']->onData($data);
        });

        $conn->on('close', function () use ($connId): void {
            $this->handleClose($connId);
        });

        $conn->on('error', function (\Throwable $e) use ($connId): void {
            Log::error('WebSocket connection error', ['error' => $e->getMessage()]);
            $this->handleClose($connId);
        });
    }

    /**
     * @var array<string, callable>
     */
    protected array $httpHandlers = [];

    /**
     * Register an HTTP POST handler for a given path.
     * Used for internal server-to-server communication (e.g. broadcasting events).
     */
    public function httpRoute(string $path, callable $handler): void
    {
        $this->httpHandlers[$path] = $handler;
    }

    protected function handleHandshake(ConnectionInterface $conn, string $connId, string $httpBuffer): void
    {
        if (! str_contains($httpBuffer, "\r\n\r\n")) {
            return;
        }

        try {
            $psrRequest = \GuzzleHttp\Psr7\Message::parseRequest($httpBuffer);

            // Handle plain HTTP requests (non-WebSocket)
            if (! $psrRequest->hasHeader('Upgrade')) {
                $this->handleHttpRequest($conn, $psrRequest);

                return;
            }

            $response = $this->negotiator->handshake($psrRequest);

            if ($response->getStatusCode() !== 101) {
                $conn->end(\GuzzleHttp\Psr7\Message::toString($response));

                return;
            }

            $origin = $psrRequest->getHeaderLine('Origin');
            if ($origin !== '' && ! $this->isOriginAllowed($origin)) {
                $this->sendErrorAndClose($conn, 'Origin not allowed');

                return;
            }

            // Route to the correct handler based on URI path
            $path = $psrRequest->getUri()->getPath();
            $handler = $this->resolveHandler($path);

            if ($handler === null) {
                $this->sendErrorAndClose($conn, "No handler registered for path: {$path}");

                return;
            }

            // Let the handler authenticate the request
            $authError = $handler->authenticate($psrRequest);
            if ($authError !== null) {
                $this->sendErrorAndClose($conn, $authError);

                return;
            }

            // Complete the WebSocket handshake
            $conn->write(\GuzzleHttp\Psr7\Message::toString($response));

            $wsConnection = new WebSocketConnection($conn);

            $messageBuffer = new MessageBuffer(
                new CloseFrameChecker,
                function ($message) use ($connId): void {
                    $entry = $this->connections[$connId] ?? null;
                    if ($entry) {
                        $entry['handler']->onMessage($connId, $message->getPayload());
                    }
                },
                function ($frame) use ($conn): void {
                    if ($frame->getOpcode() === Frame::OP_CLOSE) {
                        $conn->write((new Frame($frame->getPayload(), true, Frame::OP_CLOSE))->getContents());
                        $conn->close();
                    } elseif ($frame->getOpcode() === Frame::OP_PING) {
                        $conn->write((new Frame($frame->getPayload(), true, Frame::OP_PONG))->getContents());
                    }
                },
                true,
                null,
                null,
                null,
                function (string $data) use ($conn): void {
                    $conn->write($data);
                },
            );

            $this->connections[$connId] = [
                'handler' => $handler,
                'buffer' => $messageBuffer,
                'connection' => $wsConnection,
            ];

            $handler->onOpen($connId, $wsConnection, $psrRequest);
        } catch (\Throwable $e) {
            Log::error('WebSocket handshake error', ['error' => $e->getMessage()]);
            $conn->close();
        }
    }

    protected function resolveHandler(string $path): ?WebSocketHandler
    {
        // Exact match first
        if (isset($this->handlers[$path])) {
            return $this->handlers[$path];
        }

        // Prefix match (longest prefix wins)
        $bestMatch = null;
        $bestLength = 0;
        foreach ($this->handlers as $prefix => $handler) {
            if (str_starts_with($path, $prefix) && strlen($prefix) > $bestLength) {
                $bestMatch = $handler;
                $bestLength = strlen($prefix);
            }
        }

        return $bestMatch;
    }

    protected function handleClose(string $connId): void
    {
        $entry = $this->connections[$connId] ?? null;
        if ($entry) {
            $entry['handler']->onClose($connId);
            unset($this->connections[$connId]);
        }
    }

    protected function handleHttpRequest(ConnectionInterface $conn, \Psr\Http\Message\RequestInterface $request): void
    {
        $path = $request->getUri()->getPath();
        $handler = $this->httpHandlers[$path] ?? null;

        if ($handler === null || strtoupper($request->getMethod()) !== 'POST') {
            $conn->end("HTTP/1.1 404 Not Found\r\nConnection: close\r\n\r\n");

            return;
        }

        try {
            $handler($request);
            $conn->end("HTTP/1.1 200 OK\r\nConnection: close\r\n\r\n");
        } catch (\Throwable $e) {
            Log::error('HTTP handler error', ['error' => $e->getMessage()]);
            $conn->end("HTTP/1.1 500 Internal Server Error\r\nConnection: close\r\n\r\n");
        }
    }

    protected function isOriginAllowed(string $origin): bool
    {
        if ($this->allowedOrigins === []) {
            return true;
        }

        $originParts = parse_url($origin);
        if (! is_array($originParts) || ! isset($originParts['host'])) {
            return false;
        }

        $defaultPorts = ['http' => 80, 'https' => 443];
        $originScheme = strtolower($originParts['scheme'] ?? 'http');
        $originHost = strtolower($originParts['host']);
        $originPort = $originParts['port'] ?? null;
        $originDefaultPort = $defaultPorts[$originScheme] ?? null;

        foreach ($this->allowedOrigins as $allowed) {
            $allowedParts = parse_url($allowed);
            if (! is_array($allowedParts) || ! isset($allowedParts['host'])) {
                continue;
            }

            $allowedScheme = strtolower($allowedParts['scheme'] ?? 'http');
            $allowedHost = strtolower($allowedParts['host']);
            $allowedPort = $allowedParts['port'] ?? null;
            $allowedDefaultPort = $defaultPorts[$allowedScheme] ?? null;

            if ($originScheme !== $allowedScheme || $originHost !== $allowedHost) {
                continue;
            }

            $originHasExplicitPort = $originPort !== null && $originPort !== $originDefaultPort;
            $allowedHasExplicitPort = $allowedPort !== null && $allowedPort !== $allowedDefaultPort;

            if ($originHasExplicitPort && $allowedHasExplicitPort && $originPort !== $allowedPort) {
                continue;
            }

            return true;
        }

        return false;
    }

    protected function sendErrorAndClose(ConnectionInterface $conn, string $message): void
    {
        $response = "HTTP/1.1 403 Forbidden\r\nContent-Type: text/plain\r\nConnection: close\r\n\r\n{$message}";
        $conn->end($response);
    }

    public function getConnectionCount(): int
    {
        return count($this->connections);
    }

    protected function pingConnections(): void
    {
        foreach ($this->connections as $connId => $entry) {
            try {
                $entry['handler']->ping($connId);
            } catch (\Throwable) {
                $this->handleClose($connId);
            }
        }
    }
}
