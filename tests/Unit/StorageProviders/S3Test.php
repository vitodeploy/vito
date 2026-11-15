<?php

use App\Facades\SSH;
use App\Models\StorageProvider as StorageProviderModel;
use App\StorageProviders\S3;
use Aws\Command;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('s3 ssh upload normalizes its destination and reports an unknown size', function (): void {
    SSH::fake('upload: /tmp/backup.tar.gz to s3://fake-bucket/backups/nightly_backup.tar.gz');
    $storageProvider = StorageProviderModel::factory()->create([
        'provider' => S3::id(),
        'credentials' => [
            'key' => 'fake-key',
            'secret' => 'fake-secret',
            'region' => 'us-east-1',
            'bucket' => 'fake-bucket',
        ],
    ]);

    $result = (new S3($storageProvider))->ssh($this->server)->upload('/tmp/backup.tar.gz', '/backups//nightly backup.tar.gz');

    expect($result)->toBe(['size' => null]);
    SSH::assertExecutedContains('s3://fake-bucket/backups/nightly_backup.tar.gz');
});

test('s3 connect successful', function () {
    $storageProvider = StorageProviderModel::factory()->create([
        'provider' => S3::id(),
        'credentials' => [
            'api_url' => 'https://fake-bucket.s3.us-east-1.s3-compatible.com',
            'key' => 'fake-key',
            'secret' => 'fake-secret',
            'region' => 'us-east-1',
            'bucket' => 'fake-bucket',
            'path' => '/',
        ],
    ]);

    $s3ClientMock = $this->getMockBuilder(S3Client::class)
        ->disableOriginalConstructor()
        ->onlyMethods(['getCommand', 'execute'])
        ->getMock();

    $s3ClientMock->expects($this->once())
        ->method('getCommand')
        ->with('listBuckets')
        ->willReturn(new Command('listBuckets'));

    $s3ClientMock->expects($this->once())
        ->method('execute')
        ->willReturn(['Buckets' => []]);

    $s3 = $this->getMockBuilder(S3::class)
        ->setConstructorArgs([$storageProvider])
        ->onlyMethods(['getClient'])
        ->getMock();

    $s3->expects($this->once())
        ->method('getClient')
        ->willReturn($s3ClientMock);

    expect($s3->connect($storageProvider->credentials))->toBeTrue();
});

test('s3 connect failure', function () {
    $storageProvider = StorageProviderModel::factory()->create([
        'provider' => S3::id(),
        'credentials' => [
            'key' => 'fake-key',
            'secret' => 'fake-secret',
            'region' => 'us-east-1',
            'bucket' => 'fake-bucket',
            'path' => '/',
        ],
    ]);

    $s3ClientMock = $this->getMockBuilder(S3Client::class)
        ->disableOriginalConstructor()
        ->onlyMethods(['getCommand', 'execute'])
        ->getMock();

    $s3ClientMock->expects($this->once())
        ->method('getCommand')
        ->with('listBuckets')
        ->willReturn(new Command('listBuckets'));

    $s3ClientMock->expects($this->once())
        ->method('execute')
        ->willThrowException(new S3Exception('Error', new Command('ListBuckets')));

    $s3 = $this->getMockBuilder(S3::class)
        ->setConstructorArgs([$storageProvider])
        ->onlyMethods(['getClient'])
        ->getMock();

    $s3->expects($this->once())
        ->method('getClient')
        ->willReturn($s3ClientMock);

    expect($s3->connect($storageProvider->credentials))->toBeFalse();
});
