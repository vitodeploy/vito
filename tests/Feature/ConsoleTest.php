<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ConsoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_token(): void
    {
        $this->actingAs($this->user);

        $response = $this->post(route('console.token', $this->server), [
            'user' => $this->server->getSshUser(),
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['token']);

        $token = $response->json('token');
        $this->assertNotEmpty($token);

        // Verify token is stored in cache
        $cached = Cache::get("terminal_token:{$token}");
        $this->assertNotNull($cached);
        $this->assertEquals($this->server->id, $cached['server_id']);
        $this->assertEquals($this->user->id, $cached['user_id']);
        $this->assertEquals($this->server->getSshUser(), $cached['ssh_user']);
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

    public function test_token_is_single_use(): void
    {
        $this->actingAs($this->user);

        $response = $this->post(route('console.token', $this->server), [
            'user' => $this->server->getSshUser(),
        ]);

        $token = $response->json('token');

        // First validation should succeed
        $action = new \App\Actions\Console\GenerateTerminalToken;
        $data = $action->validate($token);
        $this->assertNotNull($data);

        // Second validation should fail (token consumed)
        $data = $action->validate($token);
        $this->assertNull($data);
    }
}
