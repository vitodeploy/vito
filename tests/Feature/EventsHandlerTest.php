<?php

use App\Actions\WebSockets\GenerateWebSocketToken;
use App\Enums\UserRole;
use App\Models\Project;
use App\Models\User;
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

/**
 * @return WebSocketConnection&object{sent: array<int, array<string, mixed>>, closed: bool}
 */
function vitoPestFeatureEventsHandlerTestFakeConnection(): WebSocketConnection
{
    return new class extends WebSocketConnection
    {
        /** @var array<int, array<string, mixed>> */
        public array $sent = [];

        public bool $closed = false;

        public function __construct() {}

        public function send(array $data): void
        {
            $this->sent[] = $data;
        }

        public function ping(): void
        {
            $this->sent[] = ['type' => 'ping'];
        }

        public function close(): void
        {
            $this->closed = true;
        }
    };
}

function vitoPestFeatureEventsHandlerTestOpen(EventsHandler $handler, string $connId, User $user, int $projectId): WebSocketConnection
{
    $token = vitoPestFeatureEventsHandlerTestGenerateEventsToken(['user_id' => $user->id, 'project_id' => $projectId]);
    $request = new PsrRequest('GET', '/ws/events?token='.$token);
    expect($handler->authenticate($request))->toBeNull();

    $connection = vitoPestFeatureEventsHandlerTestFakeConnection();
    $handler->onOpen($connId, $connection, $request);

    return $connection;
}

function vitoPestFeatureEventsHandlerTestAddMember(Project $project): User
{
    $user = User::factory()->create();
    $project->users()->create(['user_id' => $user->id, 'role' => UserRole::USER]);

    return $user;
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

test('broadcast disconnects a member removed from the project mid-session', function () {
    $project = $this->user->currentProject;
    $member = vitoPestFeatureEventsHandlerTestAddMember($project);
    $handler = vitoPestFeatureEventsHandlerTestCreateHandler();

    $ownerConnection = vitoPestFeatureEventsHandlerTestOpen($handler, 'owner', $this->user, $project->id);
    $memberConnection = vitoPestFeatureEventsHandlerTestOpen($handler, 'member', $member, $project->id);

    $handler->broadcastToProject($project->id, ['type' => 'server.updated']);
    expect(collect($memberConnection->sent)->pluck('type')->all())->toBe(['connected', 'event']);

    $project->users()->where('user_id', $member->id)->delete();
    $handler->broadcastToProject($project->id, ['type' => 'server.updated', 'data' => ['secret' => 'after-removal']]);

    expect(collect($ownerConnection->sent)->pluck('type')->all())->toBe(['connected', 'event', 'event'])
        ->and(end($memberConnection->sent))->toBe(['type' => 'error', 'message' => 'Access revoked'])
        ->and(json_encode($memberConnection->sent))->not->toContain('after-removal')
        ->and($memberConnection->closed)->toBeTrue()
        ->and($handler->getConnectionCount())->toBe(1)
        ->and($handler->getSubscribedProjectIds())->toBe([$project->id]);
});

test('ping disconnects an idle connection once the user is deleted', function () {
    $project = $this->user->currentProject;
    $member = vitoPestFeatureEventsHandlerTestAddMember($project);
    $handler = vitoPestFeatureEventsHandlerTestCreateHandler();

    $ownerConnection = vitoPestFeatureEventsHandlerTestOpen($handler, 'owner', $this->user, $project->id);
    $memberConnection = vitoPestFeatureEventsHandlerTestOpen($handler, 'member', $member, $project->id);

    $member->delete();
    $handler->ping('owner');
    $handler->ping('member');

    expect(end($ownerConnection->sent))->toBe(['type' => 'ping'])
        ->and($ownerConnection->closed)->toBeFalse()
        ->and(end($memberConnection->sent))->toBe(['type' => 'error', 'message' => 'Access revoked'])
        ->and($memberConnection->closed)->toBeTrue()
        ->and($handler->getConnectionCount())->toBe(1);
});

test('authenticate rejects a token for a project the user is not a member of', function () {
    $otherProject = Project::factory()->create();
    $token = vitoPestFeatureEventsHandlerTestGenerateEventsToken(['user_id' => $this->user->id, 'project_id' => $otherProject->id]);

    expect(vitoPestFeatureEventsHandlerTestCreateHandler()->authenticate(new PsrRequest('GET', '/ws/events?token='.$token)))
        ->toBe('Unauthorized for project');
});

test('user scoped events reach only that user', function () {
    $project = $this->user->currentProject;
    $member = vitoPestFeatureEventsHandlerTestAddMember($project);
    $handler = vitoPestFeatureEventsHandlerTestCreateHandler();

    $ownerConnection = vitoPestFeatureEventsHandlerTestOpen($handler, 'owner', $this->user, $project->id);
    $memberConnection = vitoPestFeatureEventsHandlerTestOpen($handler, 'member', $member, $project->id);

    $handler->broadcastToProject($project->id, ['type' => 'script-execution.updated'], $this->user->id);
    $handler->broadcastToAll(['type' => 'server-provider.created'], $this->user->id);
    $handler->broadcastToAll(['type' => 'bootstrap.invalidated']);

    expect(collect($ownerConnection->sent)->pluck('data.type')->filter()->values()->all())
        ->toBe(['script-execution.updated', 'server-provider.created', 'bootstrap.invalidated'])
        ->and(collect($memberConnection->sent)->pluck('data.type')->filter()->values()->all())
        ->toBe(['bootstrap.invalidated']);
});
