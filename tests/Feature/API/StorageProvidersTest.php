<?php

namespace Tests\Feature\API;

use App\Facades\FTP;
use App\Models\Backup;
use App\Models\Database;
use App\Models\StorageProvider as StorageProviderModel;
use App\Models\User;
use App\StorageProviders\Dropbox;
use App\StorageProviders\Local;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
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
        Sanctum::actingAs($this->user, ['read', 'write']);

        if ($input['provider'] === Dropbox::id()) {
            Http::fake();
        }

        if ($input['provider'] === \App\StorageProviders\FTP::id()) {
            FTP::fake();
        }

        $this->json('POST', route('api.projects.storage-providers.create', [
            'project' => $this->user->current_project_id,
        ]), $input)
            ->assertSuccessful()
            ->assertJsonFragment([
                'provider' => $input['provider'],
                'name' => $input['name'],
            ]);
    }

    public function test_see_providers_list(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        /** @var StorageProviderModel $provider */
        $provider = StorageProviderModel::factory()->create([
            'user_id' => $this->user->id,
            'provider' => Dropbox::id(),
        ]);

        $this->json('GET', route('api.projects.storage-providers', [
            'project' => $this->user->current_project_id,
        ]))
            ->assertSuccessful()
            ->assertJsonFragment([
                'provider' => $provider->provider,
                'name' => $provider->profile,
            ]);
    }

    public function test_delete_provider(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        /** @var StorageProviderModel $provider */
        $provider = StorageProviderModel::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->json('DELETE', route('api.projects.storage-providers.delete', [
            'project' => $this->user->current_project_id,
            'storageProvider' => $provider->id,
        ]))
            ->assertSuccessful()
            ->assertNoContent();
    }

    public function test_cannot_delete_provider(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        /** @var Database $database */
        $database = Database::factory()->create([
            'server_id' => $this->server,
        ]);

        /** @var StorageProviderModel $provider */
        $provider = StorageProviderModel::factory()->create([
            'user_id' => $this->user->id,
        ]);

        Backup::factory()->create([
            'server_id' => $this->server->id,
            'database_id' => $database->id,
            'storage_id' => $provider->id,
        ]);

        $this->json('DELETE', route('api.projects.storage-providers.delete', [
            'project' => $this->user->current_project_id,
            'storageProvider' => $provider->id,
        ]))
            ->assertJsonValidationErrorFor('provider');
    }

    public function test_api_user_cannot_update_other_users_storage_provider(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        $otherUser = User::factory()->create();
        $storageProvider = StorageProviderModel::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $this->json('PUT', route('api.projects.storage-providers.update', [
            'project' => $this->user->current_project_id,
            'storageProvider' => $storageProvider->id,
        ]), [
            'name' => 'hacked',
        ])
            ->assertForbidden();
    }

    public function test_api_user_cannot_delete_other_users_storage_provider(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        $otherUser = User::factory()->create();
        $storageProvider = StorageProviderModel::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $this->json('DELETE', route('api.projects.storage-providers.delete', [
            'project' => $this->user->current_project_id,
            'storageProvider' => $storageProvider->id,
        ]))
            ->assertForbidden();
    }

    public function test_api_guest_cannot_access_storage_providers(): void
    {
        $storageProvider = StorageProviderModel::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->json('GET', route('api.projects.storage-providers', [
            'project' => $this->user->current_project_id,
        ]))
            ->assertUnauthorized();

        $this->json('POST', route('api.projects.storage-providers.create', [
            'project' => $this->user->current_project_id,
        ]), [])
            ->assertUnauthorized();

        $this->json('DELETE', route('api.projects.storage-providers.delete', [
            'project' => $this->user->current_project_id,
            'storageProvider' => $storageProvider->id,
        ]))
            ->assertUnauthorized();
    }

    public function test_api_insufficient_scopes_denies_access(): void
    {
        Sanctum::actingAs($this->user, ['read']); // Only read scope

        $data = [
            'provider' => Local::id(),
            'name' => 'test',
            'path' => '/home/test',
        ];

        $this->json('POST', route('api.projects.storage-providers.create', [
            'project' => $this->user->current_project_id,
        ]), $data)
            ->assertForbidden();
    }

    public function test_api_cannot_manipulate_user_id_on_creation(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        $otherUser = User::factory()->create();

        $data = [
            'provider' => Local::id(),
            'name' => 'test',
            'path' => '/home/test',
            'user_id' => $otherUser->id,
        ];

        $this->json('POST', route('api.projects.storage-providers.create', [
            'project' => $this->user->current_project_id,
        ]), $data)
            ->assertSuccessful()
            ->assertJsonFragment([
                'provider' => Local::id(),
                'name' => 'test',
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

    public function test_api_user_can_only_see_own_storage_providers_in_list(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        $otherUser = User::factory()->create();

        $ownProvider = StorageProviderModel::factory()->create([
            'user_id' => $this->user->id,
            'profile' => 'own-provider',
        ]);

        $otherProvider = StorageProviderModel::factory()->create([
            'user_id' => $otherUser->id,
            'profile' => 'other-provider',
        ]);

        $this->json('GET', route('api.projects.storage-providers', [
            'project' => $this->user->current_project_id,
        ]))
            ->assertSuccessful()
            ->assertJsonFragment([
                'id' => $ownProvider->id,
                'provider' => $ownProvider->provider,
            ])
            ->assertJsonMissing([
                'id' => $otherProvider->id,
            ]);
    }

    /**
     * @return array<int, array<int, array<string, mixed>>>
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
