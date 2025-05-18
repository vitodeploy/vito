<?php

namespace Tests\Feature;

use App\Enums\StorageProvider;
use App\Facades\FTP;
use App\Models\Backup;
use App\Models\Database;
use App\Models\StorageProvider as StorageProviderModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class StorageProvidersTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $input
     *
     * @dataProvider createData
     */
    public function test_create(array $input): void
    {
        $this->actingAs($this->user);

        if ($input['provider'] === StorageProvider::DROPBOX) {
            Http::fake();
        }

        if ($input['provider'] === StorageProvider::FTP) {
            FTP::fake();
        }

        $this->post(route('storage-providers.store'), $input);

        if ($input['provider'] === StorageProvider::FTP) {
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
            'provider' => StorageProvider::DROPBOX,
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

    /**
     * @TODO: complete FTP tests
     *
     * @return array<int, mixed>
     */
    public static function createData(): array
    {
        return [
            [
                [
                    'provider' => StorageProvider::LOCAL,
                    'name' => 'local-test',
                    'path' => '/home/vito/backups',
                ],
            ],
            [
                [
                    'provider' => StorageProvider::LOCAL,
                    'name' => 'local-test',
                    'path' => '/home/vito/backups',
                    'global' => 1,
                ],
            ],
            [
                [
                    'provider' => StorageProvider::FTP,
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
                    'provider' => StorageProvider::FTP,
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
                    'provider' => StorageProvider::DROPBOX,
                    'name' => 'dropbox-test',
                    'token' => 'token',
                ],
            ],
            [
                [
                    'provider' => StorageProvider::DROPBOX,
                    'name' => 'dropbox-test',
                    'token' => 'token',
                    'global' => 1,
                ],
            ],
        ];
    }
}
