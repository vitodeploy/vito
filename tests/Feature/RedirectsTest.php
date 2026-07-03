<?php

namespace Tests\Feature;

use App\Enums\RedirectStatus;
use App\Facades\SSH;
use App\Models\Redirect;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class RedirectsTest extends TestCase
{
    use RefreshDatabase;

    public function test_see_redirects(): void
    {
        $this->actingAs($this->user);

        Redirect::factory()->create([
            'site_id' => $this->site->id,
        ]);

        $this->get(route('redirects', [
            'server' => $this->server,
            'site' => $this->site,
        ]))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('redirects/index'));

    }

    public function test_delete_redirect(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $redirect = Redirect::factory()->create([
            'site_id' => $this->site->id,
        ]);

        $this->delete(route('redirects.destroy', [
            'server' => $this->server,
            'site' => $this->site,
            'redirect' => $redirect,
        ]))
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseMissing('redirects', [
            'id' => $redirect->id,
        ]);
    }

    public function test_create_redirect(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $this->post(route('redirects.store', [
            'server' => $this->server,
            'site' => $this->site,
        ]), [
            'from' => 'some-path',
            'to' => 'https://example.com/redirect',
            'mode' => 301,
        ])
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('redirects', [
            'from' => 'some-path',
            'to' => 'https://example.com/redirect',
            'mode' => 301,
            'status' => RedirectStatus::READY,
        ]);
    }

    public function test_create_proxy_redirect_with_websocket(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $this->post(route('redirects.store', [
            'server' => $this->server,
            'site' => $this->site,
        ]), [
            'from' => '/app',
            'to' => 'https://backend.example.com',
            'mode' => 1000,
            'websocket' => true,
        ])
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('redirects', [
            'from' => '/app',
            'to' => 'https://backend.example.com',
            'mode' => 1000,
            'websocket' => true,
            'status' => RedirectStatus::READY,
        ]);
    }

    public function test_websocket_forced_off_when_not_proxy_mode(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $this->post(route('redirects.store', [
            'server' => $this->server,
            'site' => $this->site,
        ]), [
            'from' => '/app',
            'to' => 'https://example.com/redirect',
            'mode' => 301,
            'websocket' => true,
        ])
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('redirects', [
            'from' => '/app',
            'mode' => 301,
            'websocket' => false,
        ]);
    }

    public function test_update_redirect(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $redirect = Redirect::factory()->create([
            'site_id' => $this->site->id,
            'mode' => 301,
        ]);

        $this->put(route('redirects.update', [
            'server' => $this->server,
            'site' => $this->site,
            'redirect' => $redirect,
        ]), [
            'from' => 'updated-path',
            'to' => 'https://example.com/updated',
            'mode' => 302,
        ])
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('redirects', [
            'id' => $redirect->id,
            'from' => 'updated-path',
            'to' => 'https://example.com/updated',
            'mode' => 302,
            'status' => RedirectStatus::READY,
        ]);
    }

    public function test_update_redirect_to_proxy_with_websocket(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $redirect = Redirect::factory()->create([
            'site_id' => $this->site->id,
            'mode' => 301,
            'websocket' => false,
        ]);

        $this->put(route('redirects.update', [
            'server' => $this->server,
            'site' => $this->site,
            'redirect' => $redirect,
        ]), [
            'from' => 'ws-path',
            'to' => 'https://backend.example.com',
            'mode' => 1000,
            'websocket' => true,
        ])
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('redirects', [
            'id' => $redirect->id,
            'mode' => 1000,
            'websocket' => true,
        ]);
    }

    public function test_update_redirect_away_from_proxy_forces_websocket_off(): void
    {
        SSH::fake();

        $this->actingAs($this->user);

        $redirect = Redirect::factory()->create([
            'site_id' => $this->site->id,
            'mode' => 1000,
            'websocket' => true,
        ]);

        $this->put(route('redirects.update', [
            'server' => $this->server,
            'site' => $this->site,
            'redirect' => $redirect,
        ]), [
            'from' => 'plain-path',
            'to' => 'https://example.com/plain',
            'mode' => 301,
            'websocket' => true,
        ])
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('redirects', [
            'id' => $redirect->id,
            'mode' => 301,
            'websocket' => false,
        ]);
    }
}
