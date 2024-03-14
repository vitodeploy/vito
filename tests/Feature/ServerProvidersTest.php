<?php

namespace Tests\Feature;

use App\Enums\ServerProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ServerProvidersTest extends TestCase
{
    use RefreshDatabase;

    public function test_connect_hetzner(): void
    {
        $this->actingAs($this->user);

        Http::fake();

        $this->post(route('server-providers.connect'), [
            'provider' => ServerProvider::HETZNER,
            'name' => 'profile',
            'token' => 'token',
        ])->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('server_providers', [
            'provider' => ServerProvider::HETZNER,
            'profile' => 'profile',
        ]);
    }

    public function test_see_providers_list(): void
    {
        $this->actingAs($this->user);

        $provider = \App\Models\ServerProvider::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->get(route('server-providers'))
            ->assertSee($provider->profile);
    }

    public function test_delete_provider(): void
    {
        $this->actingAs($this->user);

        $provider = \App\Models\ServerProvider::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->delete(route('server-providers.delete', $provider->id))
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseMissing('server_providers', [
            'id' => $provider->id,
        ]);
    }
}
