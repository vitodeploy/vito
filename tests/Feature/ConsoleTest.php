<?php

use App\Actions\WebSockets\GenerateWebSocketToken;
use App\Enums\UserRole;
use App\Models\Server;
use App\Models\User;
use App\WebSocket\TerminalHandler;
use App\WebSocket\TerminalSession;
use App\WebSocket\WebSocketConnection;
use GuzzleHttp\Psr7\Request as PsrRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery\MockInterface;
use React\EventLoop\Loop;

uses(RefreshDatabase::class);

test('see console page', function () {
    $this->actingAs($this->user);

    $this->get(route('console', $this->server))
        ->assertOk();
});

test('user role cannot see console', function () {
    $this->server->project->users()->where('user_id', $this->user->id)->update([
        'role' => UserRole::USER,
    ]);

    $this->actingAs($this->user);

    $this->get(route('console', $this->server))
        ->assertForbidden();
});

test('generate token', function () {
    $this->actingAs($this->user);

    $response = $this->post(route('console.token', $this->server), [
        'user' => $this->server->getSshUser(),
    ]);

    $response->assertOk();
    $response->assertJsonStructure(['token', 'url']);

    $token = $response->json('token');
    expect($token)->not->toBeEmpty();

    $cached = Cache::get("terminal_token:{$token}");
    expect($cached)->not->toBeNull();
    expect($cached['server_id'])->toEqual($this->server->id);
    expect($cached['user_id'])->toEqual($this->user->id);
    expect($cached['ssh_user'])->toEqual($this->server->getSshUser());
});

test('generate token returns websocket url', function () {
    $this->actingAs($this->user);

    $response = $this->post(route('console.token', $this->server), [
        'user' => $this->server->getSshUser(),
    ]);

    $response->assertOk();

    $url = $response->json('url');
    expect($url)->not->toBeEmpty();
    $this->assertStringContainsString('/ws/terminal', $url);
});

test('generate token with root user', function () {
    $this->actingAs($this->user);

    $response = $this->post(route('console.token', $this->server), [
        'user' => 'root',
    ]);

    $response->assertOk();

    $token = $response->json('token');
    $cached = Cache::get("terminal_token:{$token}");
    expect($cached['ssh_user'])->toEqual('root');
});

test('generate token validation error', function () {
    $this->actingAs($this->user);

    $this->post(route('console.token', $this->server), [])
        ->assertSessionHasErrors('user');

    $this->post(route('console.token', $this->server), [
        'user' => 'invalid-user',
    ])->assertSessionHasErrors('user');
});

test('user role cannot generate token', function () {
    $this->server->project->users()->where('user_id', $this->user->id)->update([
        'role' => UserRole::USER,
    ]);

    $this->actingAs($this->user);

    $this->post(route('console.token', $this->server), [
        'user' => $this->server->getSshUser(),
    ])->assertForbidden();
});

test('token is single use', function () {
    $this->actingAs($this->user);

    $response = $this->post(route('console.token', $this->server), [
        'user' => $this->server->getSshUser(),
    ]);

    $token = $response->json('token');

    $action = new GenerateWebSocketToken;
    $data = $action->validate('terminal_token', $token);
    expect($data)->not->toBeNull();

    $data = $action->validate('terminal_token', $token);
    expect($data)->toBeNull();
});

test('token expires after ttl', function () {
    $action = new GenerateWebSocketToken;
    $result = $action->generate('terminal_token', [
        'server_id' => $this->server->id,
        'user_id' => $this->user->id,
        'ssh_user' => $this->server->getSshUser(),
    ], 30);

    expect($action->validate('terminal_token', $result['token']))->not->toBeNull();

    $result = $action->generate('terminal_token', [
        'server_id' => $this->server->id,
        'user_id' => $this->user->id,
        'ssh_user' => $this->server->getSshUser(),
    ], 30);
    Cache::forget("terminal_token:{$result['token']}");

    expect($action->validate('terminal_token', $result['token']))->toBeNull();
});

test('terminal handler authenticate with valid token', function () {
    $action = new GenerateWebSocketToken;
    $result = $action->generate('terminal_token', [
        'server_id' => $this->server->id,
        'user_id' => $this->user->id,
        'ssh_user' => $this->server->getSshUser(),
    ], 30);

    $handler = new TerminalHandler(Loop::get());

    $request = new PsrRequest('GET', '/ws/terminal?token='.$result['token'].'&cols=80&rows=24');

    $error = $handler->authenticate($request);
    expect($error)->toBeNull();
});

test('terminal handler authenticate without token', function () {
    $handler = new TerminalHandler(Loop::get());

    $request = new PsrRequest('GET', '/ws/terminal');

    $error = $handler->authenticate($request);
    expect($error)->toEqual('Missing authentication token');
});

test('terminal handler authenticate with invalid token', function () {
    $handler = new TerminalHandler(Loop::get());

    $request = new PsrRequest('GET', '/ws/terminal?token=invalid-token');

    $error = $handler->authenticate($request);
    expect($error)->toEqual('Invalid or expired token');
});

test('terminal handler authenticate with expired token', function () {
    $action = new GenerateWebSocketToken;
    $result = $action->generate('terminal_token', [
        'server_id' => $this->server->id,
        'user_id' => $this->user->id,
        'ssh_user' => $this->server->getSshUser(),
    ], 30);

    Cache::forget("terminal_token:{$result['token']}");

    $handler = new TerminalHandler(Loop::get());
    $request = new PsrRequest('GET', '/ws/terminal?token='.$result['token']);

    $error = $handler->authenticate($request);
    expect($error)->toEqual('Invalid or expired token');
});

test('terminal handler authenticate unauthorized user', function () {
    $this->server->project->users()->where('user_id', $this->user->id)->update([
        'role' => UserRole::USER,
    ]);

    $action = new GenerateWebSocketToken;
    $result = $action->generate('terminal_token', [
        'server_id' => $this->server->id,
        'user_id' => $this->user->id,
        'ssh_user' => $this->server->getSshUser(),
    ], 30);

    $handler = new TerminalHandler(Loop::get());
    $request = new PsrRequest('GET', '/ws/terminal?token='.$result['token']);

    $error = $handler->authenticate($request);
    expect($error)->toEqual('Unauthorized');
});

test('terminal handler authenticate deleted server', function () {
    $action = new GenerateWebSocketToken;
    $result = $action->generate('terminal_token', [
        'server_id' => $this->server->id,
        'user_id' => $this->user->id,
        'ssh_user' => $this->server->getSshUser(),
    ], 30);

    $this->server->forceDelete();

    $handler = new TerminalHandler(Loop::get());
    $request = new PsrRequest('GET', '/ws/terminal?token='.$result['token']);

    $error = $handler->authenticate($request);
    expect($error)->toEqual('Server not found');
});

test('terminal handler authenticate deleted user', function () {
    $action = new GenerateWebSocketToken;
    $result = $action->generate('terminal_token', [
        'server_id' => $this->server->id,
        'user_id' => $this->user->id,
        'ssh_user' => $this->server->getSshUser(),
    ], 30);

    $this->user->forceDelete();

    $handler = new TerminalHandler(Loop::get());
    $request = new PsrRequest('GET', '/ws/terminal?token='.$result['token']);

    $error = $handler->authenticate($request);
    expect($error)->toEqual('Server not found');
});

test('terminal handler rate limits connections', function () {
    $handler = new TerminalHandler(Loop::get());
    $action = new GenerateWebSocketToken;

    for ($i = 0; $i < 5; $i++) {
        $result = $action->generate('terminal_token', [
            'server_id' => $this->server->id,
            'user_id' => $this->user->id,
            'ssh_user' => $this->server->getSshUser(),
        ], 30);
        $request = new PsrRequest('GET', '/ws/terminal?token='.$result['token'].'&cols=80&rows=24');
        $error = $handler->authenticate($request);
        expect($error)->toBeNull("Connection $i should succeed");

        $reflection = new ReflectionClass($handler);
        $prop = $reflection->getProperty('connections');
        $connections = $prop->getValue($handler);
        $connections["fake-conn-$i"] = [
            'connection' => null,
            'session' => null,
            'server_id' => $this->server->id,
            'user_id' => $this->user->id,
            'ssh_user' => $this->server->getSshUser(),
            'initial_cols' => 80,
            'initial_rows' => 24,
        ];
        $prop->setValue($handler, $connections);
    }

    $result = $action->generate('terminal_token', [
        'server_id' => $this->server->id,
        'user_id' => $this->user->id,
        'ssh_user' => $this->server->getSshUser(),
    ], 30);
    $request = new PsrRequest('GET', '/ws/terminal?token='.$result['token'].'&cols=80&rows=24');
    $error = $handler->authenticate($request);
    expect($error)->toEqual('Too many connections');
});

test('terminal handler rate limit per user', function () {
    $handler = new TerminalHandler(Loop::get());
    $action = new GenerateWebSocketToken;

    $reflection = new ReflectionClass($handler);
    $prop = $reflection->getProperty('connections');
    $connections = [];
    for ($i = 0; $i < 5; $i++) {
        $connections["user1-conn-$i"] = [
            'connection' => null,
            'session' => null,
            'server_id' => $this->server->id,
            'user_id' => $this->user->id,
            'ssh_user' => $this->server->getSshUser(),
            'initial_cols' => 80,
            'initial_rows' => 24,
        ];
    }
    $prop->setValue($handler, $connections);

    $otherUser = User::factory()->create();
    $this->server->project->users()->create([
        'user_id' => $otherUser->id,
        'role' => UserRole::ADMIN,
    ]);

    $result = $action->generate('terminal_token', [
        'server_id' => $this->server->id,
        'user_id' => $otherUser->id,
        'ssh_user' => $this->server->getSshUser(),
    ], 30);
    $request = new PsrRequest('GET', '/ws/terminal?token='.$result['token'].'&cols=80&rows=24');
    $error = $handler->authenticate($request);
    expect($error)->toBeNull();
});

test('terminal handler connection count', function () {
    $handler = new TerminalHandler(Loop::get());
    expect($handler->getConnectionCount())->toEqual(0);

    $reflection = new ReflectionClass($handler);
    $prop = $reflection->getProperty('connections');
    $prop->setValue($handler, [
        'conn-1' => ['user_id' => 1],
        'conn-2' => ['user_id' => 2],
    ]);

    expect($handler->getConnectionCount())->toEqual(2);
});

test('terminal handler on close cleans up', function () {
    $handler = new TerminalHandler(Loop::get());

    $reflection = new ReflectionClass($handler);
    $prop = $reflection->getProperty('connections');
    $prop->setValue($handler, [
        'test-conn' => [
            'connection' => null,
            'session' => null,
            'server_id' => $this->server->id,
            'user_id' => $this->user->id,
            'ssh_user' => $this->server->getSshUser(),
            'initial_cols' => 80,
            'initial_rows' => 24,
        ],
    ]);

    expect($handler->getConnectionCount())->toEqual(1);

    $handler->onClose('test-conn');

    expect($handler->getConnectionCount())->toEqual(0);
});

test('terminal handler on close nonexistent connection', function () {
    $handler = new TerminalHandler(Loop::get());

    $handler->onClose('nonexistent-conn');

    expect($handler->getConnectionCount())->toEqual(0);
});

test('terminal handler on message ignores unknown connection', function () {
    $handler = new TerminalHandler(Loop::get());

    $handler->onMessage('nonexistent-conn', json_encode(['type' => 'input', 'data' => 'test']));

    expect($handler->getConnectionCount())->toEqual(0);
});

test('terminal handler on message ignores invalid json', function () {
    $handler = new TerminalHandler(Loop::get());

    $reflection = new ReflectionClass($handler);
    $prop = $reflection->getProperty('connections');
    $prop->setValue($handler, [
        'test-conn' => [
            'connection' => null,
            'session' => null,
            'server_id' => $this->server->id,
            'user_id' => $this->user->id,
            'ssh_user' => $this->server->getSshUser(),
            'initial_cols' => 80,
            'initial_rows' => 24,
        ],
    ]);

    $handler->onMessage('test-conn', 'not-json');

    expect($handler->getConnectionCount())->toEqual(1);
});

test('terminal handler on message ignores missing type', function () {
    $handler = new TerminalHandler(Loop::get());

    $reflection = new ReflectionClass($handler);
    $prop = $reflection->getProperty('connections');
    $prop->setValue($handler, [
        'test-conn' => [
            'connection' => null,
            'session' => null,
            'server_id' => $this->server->id,
            'user_id' => $this->user->id,
            'ssh_user' => $this->server->getSshUser(),
            'initial_cols' => 80,
            'initial_rows' => 24,
        ],
    ]);

    $handler->onMessage('test-conn', json_encode(['data' => 'test']));

    expect($handler->getConnectionCount())->toEqual(1);
});

test('unauthenticated user cannot access console', function () {
    $this->get(route('console', $this->server))
        ->assertRedirect();
});

test('unauthenticated user cannot generate token', function () {
    $this->post(route('console.token', $this->server), [
        'user' => $this->server->getSshUser(),
    ])->assertRedirect();
});

/**
 * @return array{TerminalHandler, WebSocketConnection&object{sent: array<int, array<string, mixed>>, closed: bool}, TerminalSession&MockInterface}
 */
function vitoPestFeatureConsoleTestOpenTerminal(Server $server, User $user): array
{
    $handler = new TerminalHandler(Loop::get());
    $connection = new class extends WebSocketConnection
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
    $session = Mockery::mock(TerminalSession::class);

    (new ReflectionProperty($handler, 'connections'))->setValue($handler, [
        'conn' => [
            'connection' => $connection,
            'session' => $session,
            'server_id' => $server->id,
            'user_id' => $user->id,
            'ssh_user' => $server->getSshUser(),
            'initial_cols' => 80,
            'initial_rows' => 24,
            'checked_at' => 0.0,
        ],
    ]);

    return [$handler, $connection, $session];
}

test('terminal input stops and the session closes once the user loses write access', function () {
    [$handler, $connection, $session] = vitoPestFeatureConsoleTestOpenTerminal($this->server, $this->user);

    $session->shouldReceive('write')->once()->with('ls');
    $session->shouldReceive('close')->once();

    $handler->onMessage('conn', json_encode(['type' => 'input', 'data' => 'ls']));

    $this->server->project->users()->where('user_id', $this->user->id)->update(['role' => UserRole::USER]);
    $handler->onMessage('conn', json_encode(['type' => 'input', 'data' => 'rm -rf /']));

    expect($connection->sent)->toBe([['type' => 'error', 'message' => 'Access revoked']])
        ->and($connection->closed)->toBeTrue()
        ->and($handler->getConnectionCount())->toBe(0);
});

test('terminal ping closes an idle session once the user is removed from the project', function () {
    [$handler, $connection, $session] = vitoPestFeatureConsoleTestOpenTerminal($this->server, $this->user);
    $session->shouldReceive('close')->once();

    $handler->ping('conn');
    expect($connection->sent)->toBe([['type' => 'ping']]);

    $this->server->project->users()->where('user_id', $this->user->id)->delete();
    $handler->ping('conn');

    expect(end($connection->sent))->toBe(['type' => 'error', 'message' => 'Access revoked'])
        ->and($connection->closed)->toBeTrue()
        ->and($handler->getConnectionCount())->toBe(0);
});

test('terminal output is rechecked at most once per interval and cut off once access is gone', function () {
    [$handler, $connection, $session] = vitoPestFeatureConsoleTestOpenTerminal($this->server, $this->user);
    $session->shouldReceive('close')->once();
    $recheck = new ReflectionMethod($handler, 'recheck');
    $connections = new ReflectionProperty($handler, 'connections');

    expect($recheck->invoke($handler, 'conn', 1))->toBeTrue();

    $this->server->project->users()->where('user_id', $this->user->id)->delete();
    expect($recheck->invoke($handler, 'conn', 1))->toBeTrue()
        ->and($connection->sent)->toBe([]);

    $state = $connections->getValue($handler);
    $state['conn']['checked_at'] = microtime(true) - 2;
    $connections->setValue($handler, $state);

    expect($recheck->invoke($handler, 'conn', 1))->toBeFalse()
        ->and($connection->sent)->toBe([['type' => 'error', 'message' => 'Access revoked']])
        ->and($connection->closed)->toBeTrue();
});
