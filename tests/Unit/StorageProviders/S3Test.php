<?php

use App\Enums\BackupType;
use App\Facades\SSH;
use App\Models\Backup;
use App\Models\BackupFile;
use App\Models\StorageProvider as StorageProviderModel;
use App\StorageProviders\S3;
use Aws\Command;
use Aws\Result;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Psr7\NoSeekStream;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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

test('S3 downloads use streaming and the same normalized key as uploads', function () {
    $storage = StorageProviderModel::factory()->create([
        'provider' => S3::id(),
        'credentials' => ['key' => 'key', 'secret' => 'secret', 'region' => 'us-east-1', 'bucket' => 'bucket', 'path' => '/back ups//'],
    ]);
    $backup = Backup::factory()->create([
        'server_id' => $this->server->id,
        'storage_id' => $storage->id,
        'type' => BackupType::FILE,
        'path' => '/home/vito/example.com',
    ]);
    $file = BackupFile::factory()->create(['backup_id' => $backup->id, 'name' => 'archive']);
    $body = Utils::streamFor('s3 archive');
    $client = Mockery::mock(S3Client::class);
    $client->shouldReceive('getObject')->once()->with([
        'Bucket' => 'bucket',
        'Key' => 'back_ups/example.com/archive.tar.gz',
        '@http' => ['stream' => true],
    ])->andReturn(new Result(['Body' => $body, 'ContentLength' => 10]));
    $provider = Mockery::mock(S3::class, [$storage])->makePartial();
    $provider->shouldReceive('getClient')->once()->andReturn($client);

    $download = $provider->download($file);
    expect($download['size'])->toBe(10);
    ob_start();
    try {
        ($download['stream'])();
        expect(ob_get_contents())->toBe('s3 archive');
    } finally {
        ob_end_clean();
    }
    expect($body->isReadable())->toBeFalse();
});

test('S3 download failures distinguish missing archives from provider errors', function (int $status) {
    $storage = StorageProviderModel::factory()->create([
        'provider' => S3::id(),
        'credentials' => ['key' => 'key', 'secret' => 'secret', 'region' => 'us-east-1', 'bucket' => 'bucket', 'path' => '/backups'],
    ]);
    $backup = Backup::factory()->create([
        'server_id' => $this->server->id, 'storage_id' => $storage->id, 'type' => BackupType::FILE, 'path' => '/home/vito/example.com',
    ]);
    $file = BackupFile::factory()->create(['backup_id' => $backup->id]);
    $client = Mockery::mock(S3Client::class);
    $client->shouldReceive('getObject')->once()->andThrow(new S3Exception('Failed', new Command('GetObject'), [
        'response' => new Response($status),
    ]));
    $provider = Mockery::mock(S3::class, [$storage])->makePartial();
    $provider->shouldReceive('getClient')->once()->andReturn($client);

    expect(fn () => $provider->download($file))->toThrow($status === 404 ? NotFoundHttpException::class : S3Exception::class);
})->with([404, 403, 503]);

test('S3 downloads preserve non-seekable streaming and endpoint addressing', function (string $endpoint, string $authority, string $path) {
    $storage = StorageProviderModel::factory()->create([
        'provider' => S3::id(),
        'credentials' => ['key' => 'key', 'secret' => 'secret', 'region' => 'us-east-1', 'bucket' => 'bucket', 'path' => '/backups', 'api_url' => $endpoint],
    ]);
    $backup = Backup::factory()->create([
        'server_id' => $this->server->id, 'storage_id' => $storage->id, 'type' => BackupType::FILE, 'path' => '/home/vito/example.com',
    ]);
    $file = BackupFile::factory()->create(['backup_id' => $backup->id, 'name' => 'archive']);
    $provider = new class($storage) extends S3
    {
        public Closure $httpHandler;

        public function getClient(): S3Client
        {
            return new S3Client([
                ...$this->clientConfig,
                'http_handler' => $this->httpHandler,
            ]);
        }
    };
    $provider->httpHandler = static function (RequestInterface $request, array $options) use ($authority, $path): FulfilledPromise {
        expect($options['stream'])->toBeTrue()
            ->and($request->getUri()->getAuthority())->toBe($authority)
            ->and($request->getUri()->getPath())->toBe($path);

        return new FulfilledPromise(new Response(200, [
            'Content-Length' => '7',
            'x-amz-checksum-sha256' => base64_encode(hash('sha256', 'archive', true)),
        ], new NoSeekStream(Utils::streamFor('archive'))));
    };

    $download = $provider->download($file);
    expect($download['size'])->toBe(7);
    ob_start();
    try {
        ($download['stream'])();
        expect(ob_get_contents())->toBe('archive');
    } finally {
        ob_end_clean();
    }
})->with([
    ['', 'bucket.s3.us-east-1.amazonaws.com', '/backups/example.com/archive.tar.gz'],
    ['https://objects.example.test:9000', 'objects.example.test:9000', '/bucket/backups/example.com/archive.tar.gz'],
]);
