<?php

namespace Tests\Feature;

use App\Facades\SSH;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_see_console(): void
    {
        $this->actingAs($this->user);

        $this->get(route('servers.console', $this->server))
            ->assertSuccessful()
            ->assertSeeText('Headless Console');
    }

    public function test_run(): void
    {
        SSH::fake('fake output');

        $this->actingAs($this->user);

        $this->post(route('servers.console.run', $this->server), [
            'user' => 'vito',
            'command' => 'ls -la',
        ])->assertStreamedContent('fake output');
    }

    public function test_run_validation_error(): void
    {
        $this->actingAs($this->user);

        $this->post(route('servers.console.run', $this->server), [
            'user' => 'vito',
        ])->assertSessionHasErrors('command');

        $this->post(route('servers.console.run', $this->server), [
            'command' => 'ls -la',
        ])->assertSessionHasErrors('user');
    }
}
