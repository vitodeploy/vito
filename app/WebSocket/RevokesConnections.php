<?php

namespace App\WebSocket;

trait RevokesConnections
{
    protected function revoke(string $connId): void
    {
        $connection = $this->connections[$connId]['connection'];
        $connection->send(['type' => 'error', 'message' => 'Access revoked']);
        $this->onClose($connId);
        $connection->close();
    }
}
