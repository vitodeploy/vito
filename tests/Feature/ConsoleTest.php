<?php

namespace Tests\Feature;

use App\Actions\Console\GenerateTerminalToken;
use App\Enums\UserRole;
use App\Models\User;
use App\WebSocket\TerminalHandler;
use GuzzleHttp\Psr7\Request as PsrRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use React\EventLoop\Loop;
use Tests\TestCase;

class ConsoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_see_console_page(): void
    {
        $this->withoutVite();
        $this->actingAs($this->user);

        $this->get(route('console', $this->server))
            ->assertOk();
    }

    public function test_user_role_cannot_see_console(): void
    {
        $this->server->project->users()->where('user_id', $this->user->id)->update([
            'role' => UserRole::USER,
        ]);

        $this->actingAs($this->user);

        $this->get(route('console', $this->server))
            ->assertForbidden();
    }

    public function test_generate_token(): void
    {
        $this->actingAs($this->user);

        $response = $this->post(route('console.token', $this->server), [
            'user' => $this->server->getSshUser(),
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['token', 'url']);

        $token = $response->json('token');
        $this->assertNotEmpty($token);

        // Verify token is stored in cache
        $cached = Cache::get("terminal_token:{$token}");
        $this->assertNotNull($cached);
        $this->assertEquals($this->server->id, $cached['server_id']);
        $this->assertEquals($this->user->id, $cached['user_id']);
        $this->assertEquals($this->server->getSshUser(), $cached['ssh_user']);
    }

    public function test_generate_token_returns_websocket_url(): void
    {
        $this->actingAs($this->user);

        $response = $this->post(route('console.token', $this->server), [
            'user' => $this->server->getSshUser(),
        ]);

        $response->assertOk();

        $url = $response->json('url');
        $this->assertNotEmpty($url);
        $this->assertStringContainsString('/ws/terminal', $url);
    }

    public function test_generate_token_with_root_user(): void
    {
        $this->actingAs($this->user);

        $response = $this->post(route('console.token', $this->server), [
            'user' => 'root',
        ]);

        $response->assertOk();

        $token = $response->json('token');
        $cached = Cache::get("terminal_token:{$token}");
        $this->assertEquals('root', $cached['ssh_user']);
    }

    public function test_generate_token_validation_error(): void
    {
        $this->actingAs($this->user);

        $this->post(route('console.token', $this->server), [])
            ->assertSessionHasErrors('user');

        $this->post(route('console.token', $this->server), [
            'user' => 'invalid-user',
        ])->assertSessionHasErrors('user');
    }

    public function test_user_role_cannot_generate_token(): void
    {
        $this->server->project->users()->where('user_id', $this->user->id)->update([
            'role' => UserRole::USER,
        ]);

        $this->actingAs($this->user);

        $this->post(route('console.token', $this->server), [
            'user' => $this->server->getSshUser(),
        ])->assertForbidden();
    }

    public function test_token_is_single_use(): void
    {
        $this->actingAs($this->user);

        $response = $this->post(route('console.token', $this->server), [
            'user' => $this->server->getSshUser(),
        ]);

        $token = $response->json('token');

        // First validation should succeed
        $action = new GenerateTerminalToken;
        $data = $action->validate($token);
        $this->assertNotNull($data);

        // Second validation should fail (token consumed)
        $data = $action->validate($token);
        $this->assertNull($data);
    }

    public function test_token_expires_after_ttl(): void
    {
        $action = new GenerateTerminalToken;
        $result = $action->generate($this->server, $this->user, $this->server->getSshUser());

        // Token should exist
        $this->assertNotNull($action->validate($result['token']));

        // Generate a new token and simulate expiry
        $result = $action->generate($this->server, $this->user, $this->server->getSshUser());
        Cache::forget("terminal_token:{$result['token']}");

        $this->assertNull($action->validate($result['token']));
    }

    public function test_terminal_handler_authenticate_with_valid_token(): void
    {
        $action = new GenerateTerminalToken;
        $result = $action->generate($this->server, $this->user, $this->server->getSshUser());

        $handler = new TerminalHandler(Loop::get());

        $request = new PsrRequest('GET', '/ws/terminal?token='.$result['token'].'&cols=80&rows=24');

        $error = $handler->authenticate($request);
        $this->assertNull($error);
    }

    public function test_terminal_handler_authenticate_without_token(): void
    {
        $handler = new TerminalHandler(Loop::get());

        $request = new PsrRequest('GET', '/ws/terminal');

        $error = $handler->authenticate($request);
        $this->assertEquals('Missing authentication token', $error);
    }

    public function test_terminal_handler_authenticate_with_invalid_token(): void
    {
        $handler = new TerminalHandler(Loop::get());

        $request = new PsrRequest('GET', '/ws/terminal?token=invalid-token');

        $error = $handler->authenticate($request);
        $this->assertEquals('Invalid or expired token', $error);
    }

    public function test_terminal_handler_authenticate_with_expired_token(): void
    {
        $action = new GenerateTerminalToken;
        $result = $action->generate($this->server, $this->user, $this->server->getSshUser());

        // Expire the token
        Cache::forget("terminal_token:{$result['token']}");

        $handler = new TerminalHandler(Loop::get());
        $request = new PsrRequest('GET', '/ws/terminal?token='.$result['token']);

        $error = $handler->authenticate($request);
        $this->assertEquals('Invalid or expired token', $error);
    }

    public function test_terminal_handler_authenticate_unauthorized_user(): void
    {
        // Change user role to USER (no write access)
        $this->server->project->users()->where('user_id', $this->user->id)->update([
            'role' => UserRole::USER,
        ]);

        $action = new GenerateTerminalToken;
        $result = $action->generate($this->server, $this->user, $this->server->getSshUser());

        $handler = new TerminalHandler(Loop::get());
        $request = new PsrRequest('GET', '/ws/terminal?token='.$result['token']);

        $error = $handler->authenticate($request);
        $this->assertEquals('Unauthorized', $error);
    }

    public function test_terminal_handler_authenticate_deleted_server(): void
    {
        $action = new GenerateTerminalToken;
        $result = $action->generate($this->server, $this->user, $this->server->getSshUser());

        // Delete the server after token generation
        $this->server->forceDelete();

        $handler = new TerminalHandler(Loop::get());
        $request = new PsrRequest('GET', '/ws/terminal?token='.$result['token']);

        $error = $handler->authenticate($request);
        $this->assertEquals('Server not found', $error);
    }

    public function test_terminal_handler_authenticate_deleted_user(): void
    {
        $action = new GenerateTerminalToken;
        $result = $action->generate($this->server, $this->user, $this->server->getSshUser());

        // Delete the user after token generation
        $this->user->forceDelete();

        $handler = new TerminalHandler(Loop::get());
        $request = new PsrRequest('GET', '/ws/terminal?token='.$result['token']);

        $error = $handler->authenticate($request);
        $this->assertEquals('Server not found', $error);
    }

    public function test_terminal_handler_rate_limits_connections(): void
    {
        $handler = new TerminalHandler(Loop::get());
        $action = new GenerateTerminalToken;

        // Authenticate MAX_CONNECTIONS_PER_USER (5) times
        for ($i = 0; $i < 5; $i++) {
            $result = $action->generate($this->server, $this->user, $this->server->getSshUser());
            $request = new PsrRequest('GET', '/ws/terminal?token='.$result['token'].'&cols=80&rows=24');
            $error = $handler->authenticate($request);
            $this->assertNull($error, "Connection $i should succeed");

            // Simulate that the connection was opened by manually adding to connections via onOpen
            // We need to call onOpen to track the connection, but it will try SSH which won't work.
            // Instead, use reflection to add a fake connection entry
            $reflection = new \ReflectionClass($handler);
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
        $result = $action->generate($this->server, $this->user, $this->server->getSshUser());
        $request = new PsrRequest('GET', '/ws/terminal?token='.$result['token'].'&cols=80&rows=24');
        $error = $handler->authenticate($request);
        $this->assertEquals('Too many connections', $error);
    }

    public function test_terminal_handler_rate_limit_per_user(): void
    {
        $handler = new TerminalHandler(Loop::get());
        $action = new GenerateTerminalToken;

        // Fill up connections for user 1
        $reflection = new \ReflectionClass($handler);
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

        $result = $action->generate($this->server, $otherUser, $this->server->getSshUser());
        $request = new PsrRequest('GET', '/ws/terminal?token='.$result['token'].'&cols=80&rows=24');
        $error = $handler->authenticate($request);
        $this->assertNull($error);
    }

    public function test_terminal_handler_connection_count(): void
    {
        $handler = new TerminalHandler(Loop::get());
        $this->assertEquals(0, $handler->getConnectionCount());

        // Add fake connections via reflection
        $reflection = new \ReflectionClass($handler);
        $prop = $reflection->getProperty('connections');
        $prop->setValue($handler, [
            'conn-1' => ['user_id' => 1],
            'conn-2' => ['user_id' => 2],
        ]);

        $this->assertEquals(2, $handler->getConnectionCount());
    }

    public function test_terminal_handler_on_close_cleans_up(): void
    {
        $handler = new TerminalHandler(Loop::get());

        // Add a fake connection
        $reflection = new \ReflectionClass($handler);
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

        $this->assertEquals(1, $handler->getConnectionCount());

        $handler->onClose('test-conn');

        $this->assertEquals(0, $handler->getConnectionCount());
    }

    public function test_terminal_handler_on_close_nonexistent_connection(): void
    {
        $handler = new TerminalHandler(Loop::get());

        // Should not throw
        $handler->onClose('nonexistent-conn');

        $this->assertEquals(0, $handler->getConnectionCount());
    }

    public function test_terminal_handler_on_message_ignores_unknown_connection(): void
    {
        $handler = new TerminalHandler(Loop::get());

        // Should not throw
        $handler->onMessage('nonexistent-conn', json_encode(['type' => 'input', 'data' => 'test']));

        $this->assertEquals(0, $handler->getConnectionCount());
    }

    public function test_terminal_handler_on_message_ignores_invalid_json(): void
    {
        $handler = new TerminalHandler(Loop::get());

        // Add a fake connection
        $reflection = new \ReflectionClass($handler);
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

        $this->assertEquals(1, $handler->getConnectionCount());
    }

    public function test_terminal_handler_on_message_ignores_missing_type(): void
    {
        $handler = new TerminalHandler(Loop::get());

        $reflection = new \ReflectionClass($handler);
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

        $this->assertEquals(1, $handler->getConnectionCount());
    }

    public function test_unauthenticated_user_cannot_access_console(): void
    {
        $this->get(route('console', $this->server))
            ->assertRedirect();
    }

    public function test_unauthenticated_user_cannot_generate_token(): void
    {
        $this->post(route('console.token', $this->server), [
            'user' => $this->server->getSshUser(),
        ])->assertRedirect();
    }
}
