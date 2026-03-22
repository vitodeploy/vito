<?php

namespace Tests\Feature;

use App\Actions\WebSockets\GenerateWebSocketToken;
use App\WebSocket\EventsHandler;
use GuzzleHttp\Psr7\Request as PsrRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventsHandlerTest extends TestCase
{
    use RefreshDatabase;

    private function createHandler(): EventsHandler
    {
        return new EventsHandler;
    }

    private function generateEventsToken(array $data): string
    {
        $result = (new GenerateWebSocketToken)->generate('events_token', $data);

        return $result['token'];
    }

    public function test_authenticate_with_valid_token(): void
    {
        $token = $this->generateEventsToken([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
        ]);

        $handler = $this->createHandler();
        $request = new PsrRequest('GET', '/ws/events?token='.$token);

        $this->assertNull($handler->authenticate($request));
    }

    public function test_authenticate_rejects_missing_and_invalid_tokens(): void
    {
        $handler = $this->createHandler();

        $this->assertEquals('Missing authentication token', $handler->authenticate(new PsrRequest('GET', '/ws/events')));
        $this->assertEquals('Invalid or expired token', $handler->authenticate(new PsrRequest('GET', '/ws/events?token=bad')));
    }

    public function test_authenticate_rate_limits_connections_per_user(): void
    {
        $handler = $this->createHandler();

        $reflection = new \ReflectionClass($handler);
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

        $token = $this->generateEventsToken([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
        ]);

        $this->assertEquals('Too many connections', $handler->authenticate(new PsrRequest('GET', '/ws/events?token='.$token)));
    }

    public function test_subscribe_rate_limit_reached(): void
    {
        $handler = $this->createHandler();
        $reflection = new \ReflectionClass($handler);

        $prop = $reflection->getProperty('userSubscribeTimestamps');
        $now = microtime(true);
        $timestamps = [];
        for ($i = 0; $i < 20; $i++) {
            $timestamps[] = $now - $i;
        }
        $prop->setValue($handler, [$this->user->id => $timestamps]);

        $method = $reflection->getMethod('isSubscribeRateLimited');
        $this->assertTrue($method->invoke($handler, $this->user->id));
    }

    public function test_subscribe_rate_limit_window_expiry(): void
    {
        $handler = $this->createHandler();
        $reflection = new \ReflectionClass($handler);

        $prop = $reflection->getProperty('userSubscribeTimestamps');
        $timestamps = [];
        for ($i = 0; $i < 20; $i++) {
            $timestamps[] = microtime(true) - 61;
        }
        $prop->setValue($handler, [$this->user->id => $timestamps]);

        $method = $reflection->getMethod('isSubscribeRateLimited');
        $this->assertFalse($method->invoke($handler, $this->user->id));
    }

    public function test_subscribe_rate_limit_is_per_user(): void
    {
        $handler = $this->createHandler();
        $reflection = new \ReflectionClass($handler);

        $prop = $reflection->getProperty('userSubscribeTimestamps');
        $now = microtime(true);
        $timestamps = [];
        for ($i = 0; $i < 20; $i++) {
            $timestamps[] = $now - $i;
        }
        $prop->setValue($handler, [$this->user->id => $timestamps]);

        $method = $reflection->getMethod('isSubscribeRateLimited');
        $this->assertTrue($method->invoke($handler, $this->user->id));
        $this->assertFalse($method->invoke($handler, 99999));
    }

    public function test_on_close_cleans_up_connection_subscription_and_timestamps(): void
    {
        $handler = $this->createHandler();
        $reflection = new \ReflectionClass($handler);

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

        $this->assertEquals(0, $handler->getConnectionCount());
        $this->assertEmpty($subProp->getValue($handler));
        $this->assertArrayNotHasKey($this->user->id, $tsProp->getValue($handler));
    }

    public function test_broadcast_to_project(): void
    {
        $handler = $this->createHandler();
        $reflection = new \ReflectionClass($handler);

        $connProp = $reflection->getProperty('connections');
        $subProp = $reflection->getProperty('projectSubscriptions');

        $mockConnection = $this->createMock(\App\WebSocket\WebSocketConnection::class);
        $mockConnection->expects($this->once())->method('send');

        $mockOtherConnection = $this->createMock(\App\WebSocket\WebSocketConnection::class);
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
    }
}
