<?php

namespace App\WebSocket;

use Illuminate\Support\Facades\Log;
use Ratchet\RFC6455\Messaging\Frame;
use React\Socket\ConnectionInterface;

/**
 * Wraps a raw TCP connection with WebSocket frame helpers.
 *
 * Handlers receive this instead of the raw TCP connection so they
 * can send JSON messages without dealing with framing themselves.
 */
class WebSocketConnection
{
    public function __construct(
        protected ConnectionInterface $tcpConnection,
    ) {}

    /**
     * Send a JSON-encoded message to the client as a WebSocket text frame.
     *
     * @param  array<string, mixed>  $data
     */
    public function send(array $data): void
    {
        try {
            $json = json_encode($data, JSON_THROW_ON_ERROR);
            $frame = new Frame($json, true, Frame::OP_TEXT);
            $this->tcpConnection->write($frame->getContents());
        } catch (\Throwable $e) {
            Log::error('WebSocket send error', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Send a raw string as a WebSocket text frame.
     */
    public function sendRaw(string $data): void
    {
        try {
            $frame = new Frame($data, true, Frame::OP_TEXT);
            $this->tcpConnection->write($frame->getContents());
        } catch (\Throwable $e) {
            Log::error('WebSocket send error', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Send a WebSocket ping frame.
     */
    public function ping(): void
    {
        $frame = new Frame('', true, Frame::OP_PING);
        $this->tcpConnection->write($frame->getContents());
    }

    /**
     * Close the underlying TCP connection.
     */
    public function close(): void
    {
        $this->tcpConnection->close();
    }

    public function getTcpConnection(): ConnectionInterface
    {
        return $this->tcpConnection;
    }
}
