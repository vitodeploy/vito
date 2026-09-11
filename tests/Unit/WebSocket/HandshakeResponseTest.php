<?php

use App\WebSocket\HandshakeResponseFactory;
use GuzzleHttp\Psr7\Request as PsrRequest;
use Ratchet\RFC6455\Handshake\RequestVerifier;
use Ratchet\RFC6455\Handshake\ServerNegotiator;

test('scalar header values are accepted despite psr7 strict validation', function () {
    $response = (new HandshakeResponseFactory)->createResponse();

    expect($response->withHeader('Sec-WebSocket-Version', 13)->getHeaderLine('Sec-WebSocket-Version'))
        ->toBe('13');
});

/**
 * Intentionally end-to-end through the vendor negotiator: this is the regression
 * guard for the guzzlehttp/psr7 3.x upgrade, which broke every handshake.
 */
test('the negotiator completes a websocket handshake with 101', function () {
    $negotiator = new ServerNegotiator(new RequestVerifier, new HandshakeResponseFactory);

    $request = new PsrRequest('GET', 'http://127.0.0.1:8085/ws/events?token=x', [
        'Host' => '127.0.0.1:8085',
        'Upgrade' => 'websocket',
        'Connection' => 'Upgrade',
        'Sec-WebSocket-Key' => base64_encode(random_bytes(16)),
        'Sec-WebSocket-Version' => '13',
    ]);

    $response = $negotiator->handshake($request);

    expect($response->getStatusCode())->toBe(101)
        ->and($response->getHeaderLine('Upgrade'))->toBe('websocket')
        ->and($response->getHeaderLine('Sec-WebSocket-Accept'))->not->toBe('');
});
