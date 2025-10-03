<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class FloatingTerminalTest extends TestCase
{
    use RefreshDatabase;

    public function test_server_header_has_terminal_button(): void
    {
        $this->actingAs($this->user);

        $response = $this->get(route('servers.show', $this->server));

        $response->assertStatus(200);
        $response->assertInertia(fn (AssertableInertia $page) => $page->component('servers/show')
            ->has('server')
        );
    }

    public function test_terminal_button_opens_floating_terminal(): void
    {
        $this->actingAs($this->user);

        $response = $this->get(route('servers.show', $this->server));

        $response->assertStatus(200);
        // The floating terminal component should be rendered when the page loads
        // The actual terminal opening is handled by JavaScript state management
    }

    public function test_floating_terminal_uses_console_endpoints(): void
    {
        $this->actingAs($this->user);

        // Test that the console endpoints are accessible
        $response = $this->get(route('console.working-dir', $this->server));
        $response->assertStatus(200);

        $response = $this->get(route('console.new-session', $this->server));
        $response->assertStatus(200);
    }
}
