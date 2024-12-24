<?php

namespace Tests\Feature;

use App\Enums\StorageProvider;
use App\Facades\FTP;
use App\Models\Backup;
use App\Models\Database;
use App\Models\StorageProvider as StorageProviderModel;
use App\Web\Pages\Settings\StorageProviders\Index;
use App\Web\Pages\Settings\StorageProviders\Widgets\StorageProvidersList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class StorageProvidersTest extends TestCase
{
    use RefreshDatabase;

    /**
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

        Livewire::test(Index::class)
            ->callAction('connect', $input)
            ->assertSuccessful();

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

        /** @var StorageProviderModel $provider */
        $provider = StorageProviderModel::factory()->create([
            'user_id' => $this->user->id,
            'provider' => StorageProvider::DROPBOX,
        ]);

        $this->get(Index::getUrl())
            ->assertSuccessful()
            ->assertSee($provider->profile);

        /** @var StorageProviderModel $provider */
        $provider = StorageProviderModel::factory()->create([
            'user_id' => $this->user->id,
            'provider' => StorageProvider::S3,
        ]);

        $this->get(Index::getUrl())
            ->assertSuccessful()
            ->assertSee($provider->profile);

        $this->get(Index::getUrl())
            ->assertSuccessful()
            ->assertSee($provider->profile);
    }

    public function test_delete_provider(): void
    {
        $this->actingAs($this->user);

        $provider = StorageProviderModel::factory()->create([
            'user_id' => $this->user->id,
        ]);

        Livewire::test(StorageProvidersList::class)
            ->callTableAction('delete', $provider->id)
            ->assertSuccessful();

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

        Livewire::test(StorageProvidersList::class)
            ->callTableAction('delete', $provider->id)
            ->assertNotified('This storage provider is being used by a backup.');

        $this->assertDatabaseHas('storage_providers', [
            'id' => $provider->id,
        ]);
    }

    /**
     * @TODO: complete FTP tests
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
