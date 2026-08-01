<?php

use App\Actions\WebSockets\GenerateWebSocketToken;
use App\Enums\UserRole;
use App\Models\User;
use App\WebSocket\TerminalHandler;
use GuzzleHttp\Psr7\Request as PsrRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
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

    // Verify token is stored in cache
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

    // First validation should succeed
    $action = new GenerateWebSocketToken;
    $data = $action->validate('terminal_token', $token);
    expect($data)->not->toBeNull();

    // Second validation should fail (token consumed)
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

    // Token should exist
    expect($action->validate('terminal_token', $result['token']))->not->toBeNull();

    // Generate a new token and simulate expiry
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

    // Expire the token
    Cache::forget("terminal_token:{$result['token']}");

    $handler = new TerminalHandler(Loop::get());
    $request = new PsrRequest('GET', '/ws/terminal?token='.$result['token']);

    $error = $handler->authenticate($request);
    expect($error)->toEqual('Invalid or expired token');
});

test('terminal handler authenticate unauthorized user', function () {
    // Change user role to USER (no write access)
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

    // Delete the server after token generation
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

    // Delete the user after token generation
    $this->user->forceDelete();

    $handler = new TerminalHandler(Loop::get());
    $request = new PsrRequest('GET', '/ws/terminal?token='.$result['token']);

    $error = $handler->authenticate($request);
    expect($error)->toEqual('Server not found');
});

test('terminal handler rate limits connections', function () {
    $handler = new TerminalHandler(Loop::get());
    $action = new GenerateWebSocketToken;

    // Authenticate MAX_CONNECTIONS_PER_USER (5) times
    for ($i = 0; $i < 5; $i++) {
        $result = $action->generate('terminal_token', [
            'server_id' => $this->server->id,
            'user_id' => $this->user->id,
            'ssh_user' => $this->server->getSshUser(),
        ], 30);
        $request = new PsrRequest('GET', '/ws/terminal?token='.$result['token'].'&cols=80&rows=24');
        $error = $handler->authenticate($request);
        expect($error)->toBeNull("Connection $i should succeed");

        // Simulate that the connection was opened by manually adding to connections via onOpen
        // We need to call onOpen to track the connection, but it will try SSH which won't work.
        // Instead, use reflection to add a fake connection entry
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

    // 6th connection should be rate limited
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

    // Fill up connections for user 1
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

    // Another user should still be able to connect
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

    // Add fake connections via reflection
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

    // Add a fake connection
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

    // Should not throw
    $handler->onClose('nonexistent-conn');

    expect($handler->getConnectionCount())->toEqual(0);
});

test('terminal handler on message ignores unknown connection', function () {
    $handler = new TerminalHandler(Loop::get());

    // Should not throw
    $handler->onMessage('nonexistent-conn', json_encode(['type' => 'input', 'data' => 'test']));

    expect($handler->getConnectionCount())->toEqual(0);
});

test('terminal handler on message ignores invalid json', function () {
    $handler = new TerminalHandler(Loop::get());

    // Add a fake connection
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

    // Should not throw on invalid JSON
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

    // Should not throw on message without type
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
