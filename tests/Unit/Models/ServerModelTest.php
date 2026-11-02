<?php

use App\Enums\ServerStatus;
use App\Facades\SSH;
use App\Helpers\SSH as SSHHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use phpseclib3\Net\SSH2;

uses(RefreshDatabase::class);

test('should have default service', function () {
    $php = $this->server->defaultService('php');
    $this->assertNotNull($php);
    $php->update(['is_default' => false]);
    expect($this->server->defaultService('php'))->not->toBeNull();
    $php->refresh();
    expect($php->is_default)->toBeTrue();
});

test('check connection is ready', function () {
    SSH::fake();

    $this->server->update(['status' => ServerStatus::DISCONNECTED]);

    $this->server->checkConnection();

    $this->assertDatabaseHas('servers', [
        'id' => $this->server->id,
        'status' => ServerStatus::READY,
    ]);
});

test('connection failed', function () {
    SSH::fake()->connectionWillFail();

    $this->server->update(['status' => ServerStatus::READY]);

    $this->server->checkConnection();

    $this->assertDatabaseHas('servers', [
        'id' => $this->server->id,
        'status' => ServerStatus::DISCONNECTED,
    ]);
});

test('exec keeps scripts and secrets on collision safe stdin inside the target user shell', function (string $command, bool $stream) {
    $ssh = (new SSHHelper)->init($this->server, 'deploy');
    $secret = "literal'\nEOF\n\$(touch /tmp/ssh-secret-escape)";
    $ssh->variables(['GIT_HTTP_TOKEN' => $secret]);

    $connection = $this->getMockBuilder(SSH2::class)
        ->disableOriginalConstructor()
        ->onlyMethods(['setTimeout', 'exec', 'getExitStatus', 'disconnect'])
        ->getMock();

    $executedCommand = null;

    $connection->expects($this->once())
        ->method('setTimeout')
        ->with(0);

    $connection->expects($this->once())
        ->method('exec')
        ->with(
            $this->isString(),
            $this->isInstanceOf(Closure::class)
        )
        ->willReturnCallback(function ($command, $callback) use (&$executedCommand) {
            $executedCommand = $command;
            $callback('');

            return '';
        });

    $connection->expects($stream ? $this->never() : $this->once())
        ->method('getExitStatus')
        ->willReturn(0);

    $connection->method('disconnect');

    $reflection = new ReflectionProperty(SSHHelper::class, 'connection');
    $reflection->setValue($ssh, $connection);

    Str::createRandomStringsUsingSequence([str_repeat('a', 32), str_repeat('b', 32)]);
    try {
        $output = $ssh->exec($command, stream: $stream, streamCallback: fn (string $output): bool => true);
    } finally {
        Str::createRandomStringsNormally();
        $ssh->disconnect();
    }

    $delimiter = 'VITO_SSH_'.str_repeat(str_contains($command, 'VITO_SSH_') ? 'b' : 'a', 32);
    $expected = "sudo -u 'deploy' bash -s <<'{$delimiter}'\n"
        ."cd ~ || { echo 'VITO_SSH_ERROR: failed to cd to home directory' >&2; exit 1; }\n"
        .'export GIT_HTTP_TOKEN='.escapeshellarg($secret).'; set -e; '.$command."\n".$delimiter;

    expect($output)->toBe('');
    expect($executedCommand)->toBe($expected)->not->toContain('bash -c');
    expect(strtok($executedCommand, "\n"))->not->toContain('GIT_HTTP_TOKEN', 'literal', 'touch');
})->with([
    "pwd\nls -la",
    "printf '%s' 'main#\nEOF\ntouch /tmp/ssh-escape\n#'",
    "printf '%s' 'VITO_SSH_aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa\nEOF'",
])->with([false, true]);
