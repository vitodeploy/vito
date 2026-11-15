<?php

namespace App\WebSocket;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\MessageInterface;

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
