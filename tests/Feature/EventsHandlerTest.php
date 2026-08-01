<?php

use App\Actions\WebSockets\GenerateWebSocketToken;
use App\WebSocket\EventsHandler;
use App\WebSocket\WebSocketConnection;
use GuzzleHttp\Psr7\Request as PsrRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function vitoPestFeatureEventsHandlerTestCreateHandler(): EventsHandler
{
    return new EventsHandler;
}

function vitoPestFeatureEventsHandlerTestGenerateEventsToken(array $data): string
{
    $result = (new GenerateWebSocketToken)->generate('events_token', $data);

    return $result['token'];
}

test('authenticate with valid token', function () {
    $token = vitoPestFeatureEventsHandlerTestGenerateEventsToken([
        'user_id' => $this->user->id,
        'project_id' => $this->user->current_project_id,
    ]);

    $handler = vitoPestFeatureEventsHandlerTestCreateHandler();
    $request = new PsrRequest('GET', '/ws/events?token='.$token);

    expect($handler->authenticate($request))->toBeNull();
});

test('authenticate rejects missing and invalid tokens', function () {
    $handler = vitoPestFeatureEventsHandlerTestCreateHandler();

    expect($handler->authenticate(new PsrRequest('GET', '/ws/events')))->toEqual('Missing authentication token');
    expect($handler->authenticate(new PsrRequest('GET', '/ws/events?token=bad')))->toEqual('Invalid or expired token');
});

test('authenticate rate limits connections per user', function () {
    $handler = vitoPestFeatureEventsHandlerTestCreateHandler();

    $reflection = new ReflectionClass($handler);
    $prop = $reflection->getProperty('connections');
    $connections = [];
    for ($i = 0; $i < 10; $i++) {
        $connections["fake-conn-$i"] = [
            'connection' => null,
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
        ];
    }
    $prop->setValue($handler, $connections);

    $token = vitoPestFeatureEventsHandlerTestGenerateEventsToken([
        'user_id' => $this->user->id,
        'project_id' => $this->user->current_project_id,
    ]);

    expect($handler->authenticate(new PsrRequest('GET', '/ws/events?token='.$token)))->toEqual('Too many connections');
});

test('subscribe rate limit reached', function () {
    $handler = vitoPestFeatureEventsHandlerTestCreateHandler();
    $reflection = new ReflectionClass($handler);

    $prop = $reflection->getProperty('userSubscribeTimestamps');
    $now = microtime(true);
    $timestamps = [];
    for ($i = 0; $i < 20; $i++) {
        $timestamps[] = $now - $i;
    }
    $prop->setValue($handler, [$this->user->id => $timestamps]);

    $method = $reflection->getMethod('isSubscribeRateLimited');
    expect($method->invoke($handler, $this->user->id))->toBeTrue();
});

test('subscribe rate limit window expiry', function () {
    $handler = vitoPestFeatureEventsHandlerTestCreateHandler();
    $reflection = new ReflectionClass($handler);

    $prop = $reflection->getProperty('userSubscribeTimestamps');
    $timestamps = [];
    for ($i = 0; $i < 20; $i++) {
        $timestamps[] = microtime(true) - 61;
    }
    $prop->setValue($handler, [$this->user->id => $timestamps]);

    $method = $reflection->getMethod('isSubscribeRateLimited');
    expect($method->invoke($handler, $this->user->id))->toBeFalse();
});

test('subscribe rate limit is per user', function () {
    $handler = vitoPestFeatureEventsHandlerTestCreateHandler();
    $reflection = new ReflectionClass($handler);

    $prop = $reflection->getProperty('userSubscribeTimestamps');
    $now = microtime(true);
    $timestamps = [];
    for ($i = 0; $i < 20; $i++) {
        $timestamps[] = $now - $i;
    }
    $prop->setValue($handler, [$this->user->id => $timestamps]);

    $method = $reflection->getMethod('isSubscribeRateLimited');
    expect($method->invoke($handler, $this->user->id))->toBeTrue();
    expect($method->invoke($handler, 99999))->toBeFalse();
});

test('on close cleans up connection subscription and timestamps', function () {
    $handler = vitoPestFeatureEventsHandlerTestCreateHandler();
    $reflection = new ReflectionClass($handler);

    $connProp = $reflection->getProperty('connections');
    $subProp = $reflection->getProperty('projectSubscriptions');
    $tsProp = $reflection->getProperty('userSubscribeTimestamps');

    $projectId = $this->user->current_project_id;
    $connProp->setValue($handler, [
        'test-conn' => [
            'connection' => null,
            'user_id' => $this->user->id,
            'project_id' => $projectId,
        ],
    ]);
    $subProp->setValue($handler, [$projectId => ['test-conn' => true]]);
    $tsProp->setValue($handler, [$this->user->id => [microtime(true)]]);

    $handler->onClose('test-conn');

    expect($handler->getConnectionCount())->toEqual(0);
    expect($subProp->getValue($handler))->toBeEmpty();
    $this->assertArrayNotHasKey($this->user->id, $tsProp->getValue($handler));
});

test('broadcast to project', function () {
    $handler = vitoPestFeatureEventsHandlerTestCreateHandler();
    $reflection = new ReflectionClass($handler);

    $connProp = $reflection->getProperty('connections');
    $subProp = $reflection->getProperty('projectSubscriptions');

    $mockConnection = $this->createMock(WebSocketConnection::class);
    $mockConnection->expects($this->once())->method('send');

    $mockOtherConnection = $this->createMock(WebSocketConnection::class);
    $mockOtherConnection->expects($this->never())->method('send');

    $projectId = $this->user->current_project_id;
    $connProp->setValue($handler, [
        'conn-1' => ['connection' => $mockConnection, 'user_id' => $this->user->id, 'project_id' => $projectId],
        'conn-2' => ['connection' => $mockOtherConnection, 'user_id' => $this->user->id, 'project_id' => 99999],
    ]);
    $subProp->setValue($handler, [
        $projectId => ['conn-1' => true],
        99999 => ['conn-2' => true],
    ]);

    $handler->broadcastToProject($projectId, ['type' => 'test', 'project_id' => $projectId]);
});
