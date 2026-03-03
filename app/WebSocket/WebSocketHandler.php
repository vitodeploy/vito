<?php

namespace App\WebSocket;

use GuzzleHttp\Psr7\Request;

/**
 * Contract for WebSocket route handlers.
 *
 * Each handler is responsible for authenticating and managing
 * its own connections once the generic WebSocket server has
 * completed the HTTP upgrade handshake.
 */
interface WebSocketHandler
{
    /**
     * Authenticate the incoming WebSocket upgrade request.
     *
     * Return a non-empty error string to reject the connection,
     * or null to allow it.
     *
     * Implementations may store validated context internally (keyed by a
     * temporary identifier from the request) for use in onOpen().
     */
    public function authenticate(Request $psrRequest): ?string;

    /**
     * Called after a successful WebSocket handshake.
     */
    public function onOpen(string $connId, WebSocketConnection $connection, Request $psrRequest): void;

    /**
     * Called when a text message is received from the client.
     */
    public function onMessage(string $connId, string $payload): void;

    /**
     * Called when the connection is closed.
     */
    public function onClose(string $connId): void;

    /**
     * Called periodically for ping/keepalive logic.
     */
    public function ping(string $connId): void;

    /**
     * Return the current connection count for this handler.
     */
    public function getConnectionCount(): int;
}
