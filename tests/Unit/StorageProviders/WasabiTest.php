<?php

namespace Tests\Unit\StorageProviders;

use App\Enums\StorageProvider;
use App\Models\StorageProvider as StorageProviderModel;
use App\StorageProviders\S3;
use App\StorageProviders\Wasabi;
use Aws\Command;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WasabiTest extends TestCase
{
    use RefreshDatabase;

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
            ->willReturn(new Command('listBuckets'));

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
            ->willReturn(new Command('listBuckets'));

        // Mock the execute method to throw an S3Exception
        $s3ClientMock->expects($this->once())
            ->method('execute')
            ->willThrowException(new S3Exception('Error', new Command('ListBuckets')));

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
}
