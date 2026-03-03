<?php

namespace App\WebSocket;

use Psr\Http\Message\RequestInterface;

interface WebSocketHandler
{
    public function authenticate(RequestInterface $psrRequest): ?string;

    public function onOpen(string $connId, WebSocketConnection $connection, RequestInterface $psrRequest): void;

    public function onMessage(string $connId, string $payload): void;

    public function onClose(string $connId): void;

    public function ping(string $connId): void;

    public function getConnectionCount(): int;
}
