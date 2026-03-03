<?php

namespace App\WebSocket;

use App\Actions\Console\GenerateTerminalToken;
use App\Models\Server;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\RequestInterface;
use React\EventLoop\LoopInterface;

class TerminalHandler implements WebSocketHandler
{
    protected const MAX_CONNECTIONS_PER_USER = 5;

    /**
     * @var array<string, array{
     *     connection: WebSocketConnection,
     *     session: ?TerminalSession,
     *     server_id: int,
     *     user_id: int,
     *     ssh_user: string,
     *     initial_cols: int,
     *     initial_rows: int,
     * }>
     */
    protected array $connections = [];

    /**
     * @var array<string, array{server_id: int, user_id: int, ssh_user: string}>
     */
    protected array $pendingAuth = [];

    public function __construct(
        protected LoopInterface $loop,
    ) {}

    public function authenticate(RequestInterface $psrRequest): ?string
    {
        $queryString = $psrRequest->getUri()->getQuery();
        parse_str($queryString, $queryParams);
        $token = $queryParams['token'] ?? '';

        if (empty($token)) {
            return 'Missing authentication token';
        }

        $tokenData = (new GenerateTerminalToken)->validate($token);
        if ($tokenData === null) {
            return 'Invalid or expired token';
        }

        $server = Server::find($tokenData['server_id']);
        $user = User::find($tokenData['user_id']);
        if (! $server || ! $user) {
            return 'Server not found';
        }

        if (! Gate::forUser($user)->allows('update', $server)) {
            return 'Unauthorized';
        }

        if ($this->userConnectionCount($tokenData['user_id']) >= self::MAX_CONNECTIONS_PER_USER) {
            return 'Too many connections';
        }

        // Store validated token data for retrieval in onOpen()
        $this->pendingAuth[$token] = $tokenData;

        return null;
    }

    public function onOpen(string $connId, WebSocketConnection $connection, RequestInterface $psrRequest): void
    {
        $queryString = $psrRequest->getUri()->getQuery();
        parse_str($queryString, $queryParams);
        $token = $queryParams['token'] ?? '';

        $tokenData = $this->pendingAuth[$token] ?? null;
        unset($this->pendingAuth[$token]);

        if (! $tokenData) {
            $connection->send(['type' => 'error', 'message' => 'Authentication failed']);
            $connection->close();

            return;
        }

        $server = Server::find($tokenData['server_id']);
        if (! $server) {
            $connection->send(['type' => 'error', 'message' => 'Server not found']);
            $connection->close();

            return;
        }

        $this->connections[$connId] = [
            'connection' => $connection,
            'session' => null,
            'server_id' => $server->id,
            'user_id' => $tokenData['user_id'],
            'ssh_user' => $tokenData['ssh_user'],
            'initial_cols' => max(1, (int) ($queryParams['cols'] ?? 80)),
            'initial_rows' => max(1, (int) ($queryParams['rows'] ?? 24)),
        ];

        $this->connectSsh($connId, $server);
    }

    public function onMessage(string $connId, string $payload): void
    {
        $entry = $this->connections[$connId] ?? null;
        if (! $entry) {
            return;
        }

        try {
            $data = json_decode($payload, true);
            if (! is_array($data) || ! isset($data['type'])) {
                return;
            }

            match ($data['type']) {
                'input' => $entry['session']?->write($data['data'] ?? ''),
                'resize' => $entry['session']?->resize(
                    (int) ($data['cols'] ?? 80),
                    (int) ($data['rows'] ?? 24),
                ),
                default => null,
            };
        } catch (\Throwable $e) {
            Log::error('Terminal message handling error', ['error' => $e->getMessage()]);
        }
    }

    public function onClose(string $connId): void
    {
        $entry = $this->connections[$connId] ?? null;
        if ($entry) {
            $entry['session']?->close();
            unset($this->connections[$connId]);
        }
    }

    public function ping(string $connId): void
    {
        $entry = $this->connections[$connId] ?? null;
        if ($entry) {
            $entry['connection']->ping();
        }
    }

    public function getConnectionCount(): int
    {
        return count($this->connections);
    }

    protected function connectSsh(string $connId, Server $server): void
    {
        $entry = $this->connections[$connId] ?? null;
        if (! $entry) {
            return;
        }

        try {
            $session = new TerminalSession(
                server: $server,
                sshUser: $entry['ssh_user'],
                loop: $this->loop,
                onOutput: function (string $data) use ($connId): void {
                    $this->connections[$connId]['connection']->send([
                        'type' => 'output',
                        'data' => $data,
                    ]);
                },
                onClose: function () use ($connId): void {
                    $entry = $this->connections[$connId] ?? null;
                    if ($entry) {
                        $entry['connection']->send([
                            'type' => 'disconnected',
                            'message' => 'SSH session closed',
                        ]);
                        $entry['connection']->close();
                    }
                },
                initialCols: $entry['initial_cols'],
                initialRows: $entry['initial_rows'],
            );

            $session->connect();
            $this->connections[$connId]['session'] = $session;

            $entry['connection']->send(['type' => 'connected']);
        } catch (\Throwable $e) {
            Log::error('Terminal SSH connection failed', [
                'server_id' => $server->id,
                'error' => $e->getMessage(),
            ]);
            $entry['connection']->send([
                'type' => 'error',
                'message' => 'Failed to connect to server',
            ]);
            $entry['connection']->close();
        }
    }

    protected function userConnectionCount(int $userId): int
    {
        return count(array_filter(
            $this->connections,
            fn (array $entry) => $entry['user_id'] === $userId,
        ));
    }
}
