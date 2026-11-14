<?php

namespace App\WebSocket;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\MessageInterface;

/**
 * PSR-7 response that tolerates scalar header values.
 *
 * `Ratchet\RFC6455\Handshake\ServerNegotiator::handshake()` sets
 * `Sec-WebSocket-Version` from `getVersionNumber(): int`. guzzlehttp/psr7 3.x
 * rejects non-string header values, which aborts every handshake.
 */
class HandshakeResponse extends Response
{
    /**
     * @param  string|int|float|bool|string[]  $value
     */
    public function withHeader(string $name, $value): MessageInterface
    {
        return parent::withHeader($name, $this->stringifyScalar($value));
    }

    /**
     * @param  string|int|float|bool|string[]  $value
     */
    public function withAddedHeader(string $name, $value): MessageInterface
    {
        return parent::withAddedHeader($name, $this->stringifyScalar($value));
    }

    /**
     * @param  string|int|float|bool|string[]  $value
     * @return string|string[]
     */
    private function stringifyScalar($value): string|array
    {
        if (is_scalar($value) && ! is_string($value)) {
            return (string) $value;
        }

        return $value;
    }
}
