<?php

namespace Tests\Feature;

use App\Facades\FTP;
use App\Models\Backup;
use App\Models\Database;
use App\Models\StorageProvider as StorageProviderModel;
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
