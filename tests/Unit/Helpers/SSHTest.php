<?php

use App\Exceptions\SSHCommandError;
use App\Facades\SSH;
use App\Helpers\SSH as SSHClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use phpseclib3\Net\SSH2;

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

function vitoPestUnitHelpersSSHTestClient(SSH2 $connection): SSHClient
{
    $client = new class extends SSHClient
    {
        public const MAX_OUTPUT_BYTES = 10;
    };
    (fn () => $this->connection = $connection)->call($client);

    return $client;
}

test('exec stops the remote command once its output exceeds the limit', function () {
    $connection = Mockery::mock(SSH2::class);
    $connection->shouldReceive('setTimeout', 'disconnect');
    $chunks = 0;
    $connection->shouldReceive('exec')->andReturnUsing(function (string $command, callable $callback) use (&$chunks): bool {
        while ($chunks < 1000) {
            $chunks++;
            if ($callback('yyyy') === true) {
                return true;
            }
        }

        return false;
    });
    $connection->shouldReceive('getExitStatus')->andReturn(0);

    expect(fn () => vitoPestUnitHelpersSSHTestClient($connection)->exec('yes'))
        ->toThrow(SSHCommandError::class, 'exceeded 10 bytes');
    expect($chunks)->toBe(3);
});

test('exec returns output within the limit', function () {
    $connection = Mockery::mock(SSH2::class);
    $connection->shouldReceive('setTimeout', 'disconnect');
    $connection->shouldReceive('exec')->andReturnUsing(fn (string $command, callable $callback): bool => $callback('ok') === true);
    $connection->shouldReceive('getExitStatus')->andReturn(0);

    expect(vitoPestUnitHelpersSSHTestClient($connection)->exec('echo ok'))->toBe('ok');
});
