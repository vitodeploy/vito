<?php

namespace Tests\Feature;

use App\Enums\StorageProvider;
use App\Models\Backup;
use App\Models\Database;
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
            ->assertSuccessful()
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

    public function test_cannot_delete_provider(): void
    {
        $this->actingAs($this->user);

        $database = Database::factory()->create([
            'server_id' => $this->server,
        ]);

        $provider = \App\Models\StorageProvider::factory()->create([
            'user_id' => $this->user->id,
        ]);

        Backup::factory()->create([
            'server_id' => $this->server->id,
            'database_id' => $database->id,
            'storage_id' => $provider->id,
        ]);

        $this->delete(route('storage-providers.delete', $provider->id))
            ->assertSessionDoesntHaveErrors()
            ->assertSessionHas('toast.type', 'error')
            ->assertSessionHas('toast.message', 'This storage provider is being used by a backup.');

        $this->assertDatabaseHas('storage_providers', [
            'id' => $provider->id,
        ]);
    }
}
