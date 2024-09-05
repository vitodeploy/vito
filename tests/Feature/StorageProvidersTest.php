<?php

namespace Tests\Feature;

use App\Enums\StorageProvider;
use App\Facades\FTP;
use App\Models\Backup;
use App\Models\Database;
use App\Models\StorageProvider as StorageProviderModel;
use App\StorageProviders\S3;
use App\StorageProviders\Wasabi;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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

        $this->post(route('settings.storage-providers.connect'), $input)
            ->assertSessionDoesntHaveErrors();

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

        $provider = \App\Models\StorageProvider::factory()->create([
            'user_id' => $this->user->id,
            'provider' => StorageProvider::DROPBOX,
        ]);

        $this->get(route('settings.storage-providers'))
            ->assertSuccessful()
            ->assertSee($provider->profile);

        $provider = \App\Models\StorageProvider::factory()->create([
            'user_id' => $this->user->id,
            'provider' => StorageProvider::S3,
        ]);

        $this->get(route('settings.storage-providers'))
            ->assertSuccessful()
            ->assertSee($provider->profile);

        $provider = \App\Models\StorageProvider::factory()->create([
            'user_id' => $this->user->id,
            'provider' => StorageProvider::WASABI,
        ]);

        $this->get(route('settings.storage-providers'))
            ->assertSuccessful()
            ->assertSee($provider->profile);
    }

    public function test_delete_provider(): void
    {
        $this->actingAs($this->user);

        $provider = \App\Models\StorageProvider::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->delete(route('settings.storage-providers.delete', $provider->id))
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

        $this->delete(route('settings.storage-providers.delete', $provider->id))
            ->assertSessionDoesntHaveErrors()
            ->assertSessionHas('toast.type', 'error')
            ->assertSessionHas('toast.message', 'This storage provider is being used by a backup.');

        $this->assertDatabaseHas('storage_providers', [
            'id' => $provider->id,
        ]);
    }

    public function test_s3_connect_successful()
    {
        $storageProvider = StorageProviderModel::factory()->create([
            'provider' => StorageProvider::S3,
            'credentials' => [
                'key' => 'fake-key',
                'secret' => 'fake-secret',
                'region' => 'us-east-1',
                'bucket' => 'fake-bucket',
                'path' => '/',
            ],
        ]);

        // Mock the S3Client
        $s3ClientMock = $this->getMockBuilder(S3Client::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCommand', 'execute'])
            ->getMock();

        // Mock the getCommand method
        $s3ClientMock->expects($this->once())
            ->method('getCommand')
            ->with('listBuckets')
            ->willReturn(new \Aws\Command('listBuckets'));

        // Mock the execute method
        $s3ClientMock->expects($this->once())
            ->method('execute')
            ->willReturn(['Buckets' => []]);

        // Mock the S3 class to return the mocked S3Client
        $s3 = $this->getMockBuilder(S3::class)
            ->setConstructorArgs([$storageProvider])
            ->onlyMethods(['getClient'])
            ->getMock();

        $s3->expects($this->once())
            ->method('getClient')
            ->willReturn($s3ClientMock);

        $this->assertTrue($s3->connect());
    }

    public function test_s3_connect_failure()
    {
        $storageProvider = StorageProviderModel::factory()->create([
            'provider' => StorageProvider::S3,
            'credentials' => [
                'key' => 'fake-key',
                'secret' => 'fake-secret',
                'region' => 'us-east-1',
                'bucket' => 'fake-bucket',
                'path' => '/',
            ],
        ]);

        // Mock the S3Client
        $s3ClientMock = $this->getMockBuilder(S3Client::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCommand', 'execute'])
            ->getMock();

        // Mock the getCommand method
        $s3ClientMock->expects($this->once())
            ->method('getCommand')
            ->with('listBuckets')
            ->willReturn(new \Aws\Command('listBuckets'));

        // Mock the execute method to throw an S3Exception
        $s3ClientMock->expects($this->once())
            ->method('execute')
            ->willThrowException(new S3Exception('Error', new \Aws\Command('ListBuckets')));

        // Mock the S3 class to return the mocked S3Client
        $s3 = $this->getMockBuilder(S3::class)
            ->setConstructorArgs([$storageProvider])
            ->onlyMethods(['getClient'])
            ->getMock();

        $s3->expects($this->once())
            ->method('getClient')
            ->willReturn($s3ClientMock);

        $this->assertFalse($s3->connect());
    }

    public function test_wasabi_connect_successful()
    {
        $storageProvider = StorageProviderModel::factory()->create([
            'provider' => StorageProvider::WASABI,
            'credentials' => [
                'key' => 'fake-key',
                'secret' => 'fake-secret',
                'region' => 'us-east-1',
                'bucket' => 'fake-bucket',
                'path' => '/',
            ],
        ]);

        // Mock the S3Client (Wasabi uses S3-compatible API)
        $s3ClientMock = $this->getMockBuilder(S3Client::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCommand', 'execute'])
            ->getMock();

        // Mock the getCommand method
        $s3ClientMock->expects($this->once())
            ->method('getCommand')
            ->with('listBuckets')
            ->willReturn(new \Aws\Command('listBuckets'));

        // Mock the execute method
        $s3ClientMock->expects($this->once())
            ->method('execute')
            ->willReturn(['Buckets' => []]);

        // Mock the Wasabi class to return the mocked S3Client
        $wasabi = $this->getMockBuilder(Wasabi::class)
            ->setConstructorArgs([$storageProvider])
            ->onlyMethods(['getClient'])
            ->getMock();

        $wasabi->expects($this->once())
            ->method('getClient')
            ->willReturn($s3ClientMock);

        $this->assertTrue($wasabi->connect());
    }

    public function test_wasabi_connect_failure()
    {
        $storageProvider = StorageProviderModel::factory()->create([
            'provider' => StorageProvider::WASABI,
            'credentials' => [
                'key' => 'fake-key',
                'secret' => 'fake-secret',
                'region' => 'us-east-1',
                'bucket' => 'fake-bucket',
                'path' => '/',
            ],
        ]);

        // Mock the S3Client (Wasabi uses S3-compatible API)
        $s3ClientMock = $this->getMockBuilder(S3Client::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getCommand', 'execute'])
            ->getMock();

        // Mock the getCommand method
        $s3ClientMock->expects($this->once())
            ->method('getCommand')
            ->with('listBuckets')
            ->willReturn(new \Aws\Command('listBuckets'));

        // Mock the execute method to throw an S3Exception
        $s3ClientMock->expects($this->once())
            ->method('execute')
            ->willThrowException(new S3Exception('Error', new \Aws\Command('ListBuckets')));

        // Mock the Wasabi class to return the mocked S3Client
        $wasabi = $this->getMockBuilder(Wasabi::class)
            ->setConstructorArgs([$storageProvider])
            ->onlyMethods(['getClient'])
            ->getMock();

        $wasabi->expects($this->once())
            ->method('getClient')
            ->willReturn($s3ClientMock);

        $this->assertFalse($wasabi->connect());
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
