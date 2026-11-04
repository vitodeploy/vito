<?php

use App\Exceptions\SSHCommandError;
use App\Exceptions\SSHError;
use App\Facades\SSH;
use App\Helpers\SSH as SSHHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use phpseclib3\Net\SFTP;

uses(RefreshDatabase::class);

test('write propagates the failure and cleans up the local temporary file', function () {
    Storage::fake('local');

    $ssh = SSH::fake();
    $ssh->execWillFail();

    expect(fn () => $this->server->ssh()->write('/home/vito/example.com/.env', 'APP_NAME=TestApp', 'vito'))
        ->toThrow(SSHCommandError::class);

    expect(Storage::disk('local')->files())->toBeEmpty();
});

test('write cleans up the local temporary file on success', function () {
    Storage::fake('local');

    SSH::fake();

    $this->server->ssh()->write('/home/vito/example.com/.env', 'APP_NAME=TestApp', 'vito');

    expect(Storage::disk('local')->files())->toBeEmpty();
});

test('write removes the remote temporary file on both branches', function () {
    SSH::fake();

    $this->server->ssh()->write('/home/vito/example.com/.env', 'APP_NAME=TestApp', 'vito');

    SSH::assertExecutedContains("> '/home/vito/example.com/.env'; then");
    SSH::assertExecutedContains("else\n    rm -f ");
    SSH::assertExecutedContains('exit 1');
});

test('write quotes the destination path', function () {
    SSH::fake();

    $this->server->ssh()->write('/home/vito/legacy path/.env', 'APP_NAME=TestApp', 'vito');

    SSH::assertExecutedContains("> '/home/vito/legacy path/.env'");
});

test('SFTP download streams chunks and detects incomplete transfers', function (bool $success, int $expectedSize) {
    $connection = Mockery::mock(SFTP::class);
    $connection->shouldReceive('setTimeout')->once()->with(0);
    $connection->shouldReceive('get')->once()->with('/backup.sql.gz', Mockery::type('callable'))
        ->andReturnUsing(function (string $path, callable $onChunk) use ($success): bool {
            $onChunk('first');
            $onChunk('second');

            return $success;
        });
    $connection->shouldReceive('disconnect')->once();
    $ssh = new SSHHelper;
    (new ReflectionProperty(SSHHelper::class, 'connection'))->setValue($ssh, $connection);
    $content = '';
    $download = function () use ($ssh, $expectedSize, &$content): void {
        $ssh->streamDownload('/backup.sql.gz', function (string $chunk) use (&$content): void {
            $content .= $chunk;
        }, $expectedSize);
    };

    if ($success && $expectedSize === 11) {
        $download();
    } else {
        expect($download)->toThrow(SSHError::class);
    }
    expect($content)->toBe('firstsecond');
    $ssh->disconnect();
})->with([[true, 11], [false, 11], [true, 20]]);

test('SFTP download preflight checks a regular file and its current size', function (bool $exists, int|false $size, ?int $expected) {
    $connection = Mockery::mock(SFTP::class);
    $connection->shouldReceive('clearStatCache')->once();
    $connection->shouldReceive('is_file')->once()->with('/backup.tar.gz')->andReturn($exists);
    if ($exists) {
        $connection->shouldReceive('filesize')->once()->with('/backup.tar.gz')->andReturn($size);
    }
    $connection->shouldReceive('disconnect')->once();
    $ssh = new SSHHelper;
    (new ReflectionProperty(SSHHelper::class, 'connection'))->setValue($ssh, $connection);

    expect($ssh->fileSize('/backup.tar.gz'))->toBe($expected);
    $ssh->disconnect();
})->with([[true, 123, 123], [true, 0, 0], [true, false, null], [false, false, null]]);
