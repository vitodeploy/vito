<?php

namespace Tests\Feature;

use App\Enums\ServerProvider;
use App\Http\Livewire\ServerProviders\ConnectProvider;
use App\Http\Livewire\ServerProviders\ProvidersList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class ServerProvidersTest extends TestCase
{
    use RefreshDatabase;

    public function test_connect_hetzner(): void
    {
        $this->actingAs($this->user);

        Http::fake();

        Livewire::test(ConnectProvider::class)
            ->set('provider', ServerProvider::HETZNER)
            ->set('name', 'profile')
            ->set('token', 'token')
            ->call('connect')
            ->assertSuccessful();

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

        Livewire::test(ProvidersList::class)
            ->assertSee([
                $provider->profile,
            ]);
    }

    public function test_delete_provider(): void
    {
        $this->actingAs($this->user);

        $provider = \App\Models\ServerProvider::factory()->create([
            'user_id' => $this->user->id,
        ]);

        Livewire::test(ProvidersList::class)
            ->set('deleteId', $provider->id)
            ->call('delete')
            ->assertSuccessful();

        $this->assertDatabaseMissing('server_providers', [
            'id' => $provider->id,
        ]);
    }
}
