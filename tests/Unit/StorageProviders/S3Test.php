<?php

namespace Tests\Unit\StorageProviders;

use App\Enums\StorageProvider;
use App\Models\StorageProvider as StorageProviderModel;
use App\StorageProviders\S3;
use Aws\Command;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class S3Test extends TestCase
{
    use RefreshDatabase;

    public function test_s3_connect_successful()
    {
        $storageProvider = StorageProviderModel::factory()->create([
            'provider' => StorageProvider::S3,
            'credentials' => [
                'api_url' => 'https://fake-bucket.s3.us-east-1.s3-compatible.com',
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
            ->willReturn(new Command('listBuckets'));

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
            ->willReturn(new Command('listBuckets'));

        // Mock the execute method to throw an S3Exception
        $s3ClientMock->expects($this->once())
            ->method('execute')
            ->willThrowException(new S3Exception('Error', new Command('ListBuckets')));

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
}
