<?php

namespace App\WebSocket;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

class HandshakeResponseFactory implements ResponseFactoryInterface
{
    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        return new HandshakeResponse($code, [], null, '1.1', $reasonPhrase);
    }
}
