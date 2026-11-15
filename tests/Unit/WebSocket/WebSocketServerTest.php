<?php

use App\WebSocket\LimitedMessageBuffer;
use App\WebSocket\WebSocketConnection;
use App\WebSocket\WebSocketHandler;
use App\WebSocket\WebSocketServer;
use Evenement\EventEmitter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Psr\Http\Message\RequestInterface;
use Ratchet\RFC6455\Messaging\Frame;
use React\EventLoop\StreamSelectLoop;
use React\Socket\ConnectionInterface;
use React\Stream\WritableStreamInterface;

uses(RefreshDatabase::class);

/**
 * @return ConnectionInterface&object{written: string, closed: bool}
 */
function vitoPestUnitWebSocketServerTestConnection(): ConnectionInterface
{
    return new class extends EventEmitter implements ConnectionInterface
    {
        public string $written = '';

        public bool $closed = false;

        public function write($data): bool
        {
            $this->written .= $data;

            return true;
        }

        public function end($data = null): void
        {
            $this->written .= (string) $data;
            $this->close();
        }

        public function close(): void
        {
            if (! $this->closed) {
                $this->closed = true;
                $this->emit('close');
            }
        }

        public function isReadable(): bool
        {
            return ! $this->closed;
        }

        public function isWritable(): bool
        {
            return ! $this->closed;
        }

        public function pause(): void {}

        public function resume(): void {}

        public function pipe(WritableStreamInterface $dest, array $options = []): WritableStreamInterface
        {
            return $dest;
        }

        public function getRemoteAddress(): ?string
        {
            return null;
        }

        public function getLocalAddress(): ?string
        {
            return null;
        }
    };
}

/**
 * @return WebSocketHandler&object{messages: array<int, string>}
 */
function vitoPestUnitWebSocketServerTestHandler(?Closure $onPing = null): WebSocketHandler
{
    return new class($onPing) implements WebSocketHandler
    {
        /** @var array<int, string> */
        public array $messages = [];

        public function __construct(protected ?Closure $onPing) {}

        public function authenticate(RequestInterface $psrRequest): ?string
        {
            return null;
        }

        public function onOpen(string $connId, WebSocketConnection $connection, RequestInterface $psrRequest): void {}

        public function onMessage(string $connId, string $payload): void
        {
            $this->messages[] = $payload;
        }

        public function onClose(string $connId): void {}

        public function ping(string $connId): void
        {
            if ($this->onPing) {
                ($this->onPing)();
            }
        }

        public function getConnectionCount(): int
        {
            return 0;
        }
    };
}

/**
 * @param  array<int, string>  $allowedOrigins
 * @return array{ConnectionInterface&object{written: string, closed: bool}, WebSocketHandler&object{messages: array<int, string>}}
 */
function vitoPestUnitWebSocketServerTestOpen(string $origin, array $allowedOrigins = ['https://vito.example.com']): array
{
    $server = new WebSocketServer(new StreamSelectLoop, 50, $allowedOrigins);
    $handler = vitoPestUnitWebSocketServerTestHandler();
    $server->route('/ws/test', $handler);

    $connection = vitoPestUnitWebSocketServerTestConnection();
    $server->handleConnection($connection);
    $connection->emit('data', [implode("\r\n", [
        'GET /ws/test HTTP/1.1',
        'Host: vito.example.com',
        'Upgrade: websocket',
        'Connection: Upgrade',
        'Sec-WebSocket-Key: '.base64_encode(random_bytes(16)),
        'Sec-WebSocket-Version: 13',
        'Origin: '.$origin,
    ])."\r\n\r\n"]);

    return [$connection, $handler];
}

function vitoPestUnitWebSocketServerTestFrame(string $payload, bool $final, int $opcode): string
{
    return (new Frame($payload, $final, $opcode))->maskPayload()->getContents();
}

test('origin must match scheme, host and effective port', function (string $origin, array $allowed, bool $accepted) {
    [$connection] = vitoPestUnitWebSocketServerTestOpen($origin, $allowed);

    expect(str_starts_with($connection->written, 'HTTP/1.1 101'))->toBe($accepted)
        ->and(str_contains($connection->written, 'Origin not allowed'))->toBe(! $accepted);
})->with([
    'same origin' => ['https://vito.example.com', ['https://vito.example.com'], true],
    'explicit default port' => ['https://vito.example.com:443', ['https://vito.example.com'], true],
    'allowed entry with path' => ['https://vito.example.com', ['https://vito.example.com/'], true],
    'case insensitive host' => ['https://VITO.example.com', ['https://vito.example.com'], true],
    'other port on allowed host' => ['https://vito.example.com:8443', ['https://vito.example.com'], false],
    'default port when a custom one is allowed' => ['http://localhost', ['http://localhost:8000'], false],
    'other custom port' => ['http://localhost:9000', ['http://localhost:8000'], false],
    'matching custom port' => ['http://localhost:8000', ['http://localhost:8000'], true],
    'other scheme' => ['http://vito.example.com', ['https://vito.example.com'], false],
    'other host' => ['https://evil.example.com', ['https://vito.example.com'], false],
    'suffix host' => ['https://vito.example.com.evil.test', ['https://vito.example.com'], false],
    'garbage' => ['null', ['https://vito.example.com'], false],
]);

test('a message may have at most the fragment cap of frames', function (int $frames, bool $accepted) {
    [$connection, $handler] = vitoPestUnitWebSocketServerTestOpen('https://vito.example.com');

    $data = vitoPestUnitWebSocketServerTestFrame('a', false, Frame::OP_TEXT);
    for ($i = 0; $i < $frames - 2; $i++) {
        $data .= vitoPestUnitWebSocketServerTestFrame('', false, Frame::OP_CONTINUE);
    }
    $data .= vitoPestUnitWebSocketServerTestFrame('b', true, Frame::OP_CONTINUE);
    $connection->emit('data', [$data.$data]);

    expect($handler->messages)->toBe($accepted ? ['ab', 'ab'] : [])
        ->and($connection->closed)->toBe(! $accepted)
        ->and(str_contains($connection->written, pack('n', Frame::CLOSE_TOO_BIG).'Too many fragments'))->toBe(! $accepted);
})->with([
    'single frame' => [2, true],
    'at the cap' => [LimitedMessageBuffer::MAX_FRAGMENTS, true],
    'one over the cap' => [LimitedMessageBuffer::MAX_FRAGMENTS + 1, false],
]);

test('an empty fragment flood closes the connection', function () {
    [$connection, $handler] = vitoPestUnitWebSocketServerTestOpen('https://vito.example.com');

    $frames = vitoPestUnitWebSocketServerTestFrame('', false, Frame::OP_TEXT);
    for ($i = 0; $i < LimitedMessageBuffer::MAX_FRAGMENTS + 10; $i++) {
        $frames .= vitoPestUnitWebSocketServerTestFrame('', false, Frame::OP_CONTINUE);
    }
    $connection->emit('data', [$frames]);

    expect($connection->closed)->toBeTrue()
        ->and($handler->messages)->toBe([]);
});

test('a ping that throws closes the transport', function () {
    $server = new WebSocketServer(new StreamSelectLoop, 50, []);
    $server->route('/ws/test', vitoPestUnitWebSocketServerTestHandler(fn () => throw new RuntimeException('database is down')));

    $connection = vitoPestUnitWebSocketServerTestConnection();
    $server->handleConnection($connection);
    $connection->emit('data', [implode("\r\n", [
        'GET /ws/test HTTP/1.1',
        'Host: vito.example.com',
        'Upgrade: websocket',
        'Connection: Upgrade',
        'Sec-WebSocket-Key: '.base64_encode(random_bytes(16)),
        'Sec-WebSocket-Version: 13',
    ])."\r\n\r\n"]);

    expect($server->getConnectionCount())->toBe(1);

    (new ReflectionMethod($server, 'pingConnections'))->invoke($server);

    expect($connection->closed)->toBeTrue()
        ->and($server->getConnectionCount())->toBe(0);
});

test('messages over the size limit close the connection', function () {
    [$connection, $handler] = vitoPestUnitWebSocketServerTestOpen('https://vito.example.com');

    $connection->emit('data', [vitoPestUnitWebSocketServerTestFrame(str_repeat('a', 1024), true, Frame::OP_TEXT)]);
    expect($handler->messages)->toHaveCount(1);

    $connection->emit('data', [vitoPestUnitWebSocketServerTestFrame(str_repeat('a', 1048577), true, Frame::OP_TEXT)]);

    expect($connection->closed)->toBeTrue()
        ->and($connection->written)->toContain(pack('n', Frame::CLOSE_TOO_BIG))
        ->and($handler->messages)->toHaveCount(1);
});
