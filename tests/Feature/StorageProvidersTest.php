<?php

namespace Tests\Feature;

use App\Enums\StorageProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StorageProvidersTest extends TestCase
{
    use RefreshDatabase;

    public function test_connect_dropbox(): void
    {
        $this->actingAs($this->user);

        Http::fake();

        $this->post(route('storage-providers.connect'), [
            'provider' => StorageProvider::DROPBOX,
            'name' => 'profile',
            'token' => 'token',
        ])->assertSessionDoesntHaveErrors();

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
            'provider' => StorageProvider::DROPBOX,
        ]);

        $this->get(route('storage-providers'))
            ->assertSee($provider->profile);
    }

    public function test_delete_provider(): void
    {
        $this->actingAs($this->user);

        $provider = \App\Models\StorageProvider::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->delete(route('storage-providers.delete', $provider->id))
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseMissing('storage_providers', [
            'id' => $provider->id,
        ]);
    }
}
