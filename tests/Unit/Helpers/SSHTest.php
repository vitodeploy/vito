<?php

use App\Exceptions\SSHCommandError;
use App\Facades\SSH;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

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
