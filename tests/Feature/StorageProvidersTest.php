<?php

namespace Tests\Feature;

use App\Facades\FTP;
use App\Models\Backup;
use App\Models\Database;
use App\Models\StorageProvider as StorageProviderModel;
use App\Models\User;
use App\StorageProviders\Dropbox;
use App\StorageProviders\Local;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StorageProvidersTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $input
     */
    #[DataProvider('createData')]
    public function test_create(array $input): void
    {
        $this->actingAs($this->user);

        if ($input['provider'] === Dropbox::id()) {
            Http::fake();
        }

        if ($input['provider'] === \App\StorageProviders\FTP::id()) {
            FTP::fake();
        }

        $this->post(route('storage-providers.store'), $input);

        if ($input['provider'] === \App\StorageProviders\FTP::id()) {
            FTP::assertConnected($input['host']);
        }

        $this->assertDatabaseHas('storage_providers', [
            'provider' => $input['provider'],
            'profile' => $input['name'],
            'project_id' => isset($input['global']) ? null : $this->user->current_project_id,
        ]);
    }

    public function test_see_providers_list(): void
    {
        $this->actingAs($this->user);

        StorageProviderModel::factory()->create([
            'user_id' => $this->user->id,
            'provider' => Dropbox::id(),
        ]);

        $this->get(route('storage-providers'))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('storage-providers/index'));
    }

    public function test_delete_provider(): void
    {
        $this->actingAs($this->user);

        $provider = StorageProviderModel::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->delete(route('storage-providers.destroy', ['storageProvider' => $provider->id]));

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

        $provider = StorageProviderModel::factory()->create([
            'user_id' => $this->user->id,
        ]);

        Backup::factory()->create([
            'server_id' => $this->server->id,
            'database_id' => $database->id,
            'storage_id' => $provider->id,
        ]);

        $this->delete(route('storage-providers.destroy', ['storageProvider' => $provider->id]))
            ->assertSessionHasErrors([
                'provider' => 'This storage provider is being used by a backup.',
            ]);

        $this->assertDatabaseHas('storage_providers', [
            'id' => $provider->id,
        ]);
    }

    public function test_user_cannot_update_other_users_storage_provider(): void
    {
        $this->actingAs($this->user);

        $otherUser = User::factory()->create();
        $storageProvider = StorageProviderModel::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $this->patch(route('storage-providers.update', $storageProvider), [
            'name' => 'hacked',
        ])
            ->assertForbidden();
    }

    public function test_user_cannot_delete_other_users_storage_provider(): void
    {
        $this->actingAs($this->user);

        $otherUser = User::factory()->create();
        $storageProvider = StorageProviderModel::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $this->delete(route('storage-providers.destroy', $storageProvider))
            ->assertForbidden();
    }

    public function test_guest_cannot_access_storage_providers(): void
    {
        $storageProvider = StorageProviderModel::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->get(route('storage-providers'))
            ->assertRedirect('/');

        $this->post(route('storage-providers.store'), [])
            ->assertRedirect('/');

        $this->patch(route('storage-providers.update', $storageProvider), [])
            ->assertRedirect('/');

        $this->delete(route('storage-providers.destroy', $storageProvider))
            ->assertRedirect('/');
    }

    public function test_cannot_manipulate_user_id_on_creation(): void
    {
        $this->actingAs($this->user);

        $otherUser = User::factory()->create();

        $this->post(route('storage-providers.store'), [
            'provider' => Local::id(),
            'name' => 'test',
            'path' => '/home/test',
            'user_id' => $otherUser->id,
        ]);

        $this->assertDatabaseHas('storage_providers', [
            'profile' => 'test',
            'provider' => Local::id(),
            'user_id' => $this->user->id,
        ]);

        $this->assertDatabaseMissing('storage_providers', [
            'profile' => 'test',
            'provider' => Local::id(),
            'user_id' => $otherUser->id,
        ]);
    }

    public function test_cannot_transfer_ownership_via_update(): void
    {
        $this->actingAs($this->user);

        $otherUser = User::factory()->create();
        $storageProvider = StorageProviderModel::factory()->create([
            'user_id' => $this->user->id,
            'profile' => 'original',
        ]);

        $this->patch(route('storage-providers.update', $storageProvider), [
            'name' => 'updated',
            'user_id' => $otherUser->id,
        ]);

        $storageProvider->refresh();

        $this->assertEquals($this->user->id, $storageProvider->user_id);
        $this->assertNotEquals($otherUser->id, $storageProvider->user_id);
    }

    public function test_user_can_only_see_own_storage_providers_in_list(): void
    {
        $this->actingAs($this->user);

        $otherUser = User::factory()->create();

        $ownProvider = StorageProviderModel::factory()->create([
            'user_id' => $this->user->id,
            'profile' => 'own-provider',
        ]);

        $otherProvider = StorageProviderModel::factory()->create([
            'user_id' => $otherUser->id,
            'profile' => 'other-provider',
        ]);

        $response = $this->get(route('storage-providers'))
            ->assertSuccessful()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('storage-providers/index'));

        $response->assertInertia(fn (AssertableInertia $page) => $page->has('storageProviders.data')
            ->where('storageProviders.data.0.id', $ownProvider->id)
            ->whereNot('storageProviders.data.0.id', $otherProvider->id)
        );
    }

    /**
     * @return array<int, mixed>
     */
    public static function createData(): array
    {
        return [
            [
                [
                    'provider' => Local::id(),
                    'name' => 'local-test',
                    'path' => '/home/vito/backups',
                ],
            ],
            [
                [
                    'provider' => Local::id(),
                    'name' => 'local-test',
                    'path' => '/home/vito/backups',
                    'global' => 1,
                ],
            ],
            [
                [
                    'provider' => \App\StorageProviders\FTP::id(),
                    'name' => 'ftp-test',
                    'host' => '1.2.3.4',
                    'port' => '22',
                    'path' => '/home/vito',
                    'username' => 'username',
                    'password' => 'password',
                    'ssl' => 1,
                    'passive' => 1,
                ],
            ],
            [
                [
                    'provider' => \App\StorageProviders\FTP::id(),
                    'name' => 'ftp-test',
                    'host' => '1.2.3.4',
                    'port' => '22',
                    'path' => '/home/vito',
                    'username' => 'username',
                    'password' => 'password',
                    'ssl' => 1,
                    'passive' => 1,
                    'global' => 1,
                ],
            ],
            [
                [
                    'provider' => Dropbox::id(),
                    'name' => 'dropbox-test',
                    'token' => 'token',
                ],
            ],
            [
                [
                    'provider' => Dropbox::id(),
                    'name' => 'dropbox-test',
                    'token' => 'token',
                    'global' => 1,
                ],
            ],
        ];
    }
}
