<?php

namespace Tests\Feature\Http;

use App\Enums\StorageProvider;
use App\Http\Livewire\StorageProviders\ProvidersList;
use App\Http\Livewire\StorageProviders\ConnectProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class StorageProvidersTest extends TestCase
{
    use RefreshDatabase;

    public function test_connect_dropbox(): void
    {
        $this->actingAs($this->user);

        Http::fake();

        Livewire::test(ConnectProvider::class)
            ->set('provider', StorageProvider::DROPBOX)
            ->set('name', 'profile')
            ->set('token', 'token')
            ->call('connect')
            ->assertSuccessful();

        $this->assertDatabaseHas('storage_providers', [
            'provider' => StorageProvider::DROPBOX,
            'profile' => 'profile',
        ]);
    }

    public function test_see_providers_list(): void
    {
        $this->actingAs($this->user);

        $provider = \App\Models\StorageProvider::factory()->create([
            'user_id' => $this->user->id,
            'provider' => StorageProvider::DROPBOX
        ]);

        Livewire::test(ProvidersList::class)
            ->assertSee([
                $provider->profile,
            ]);
    }

    public function test_delete_provider(): void
    {
        $this->actingAs($this->user);

        $provider = \App\Models\StorageProvider::factory()->create([
            'user_id' => $this->user->id,
        ]);

        Livewire::test(ProvidersList::class)
            ->set('deleteId', $provider->id)
            ->call('delete')
            ->assertSuccessful();

        $this->assertDatabaseMissing('storage_providers', [
            'id' => $provider->id,
        ]);
    }
}
