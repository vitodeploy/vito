<?php

use App\Enums\ServerStatus;
use App\Facades\SSH;
use App\Helpers\SSH as SSHHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

test('exec keeps multiline commands inside the target user shell', function (string $command) {
    $ssh = (new SSHHelper)->init($this->server, 'deploy');

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

    $connection->expects($this->once())
        ->method('getExitStatus')
        ->willReturn(0);

    $connection->method('disconnect');

    $reflection = new ReflectionProperty(SSHHelper::class, 'connection');
    $reflection->setValue($ssh, $connection);

    $output = $ssh->exec($command);
    $ssh->disconnect();

    $expected = "sudo -u 'deploy' bash -c ".escapeshellarg(
        "cd ~ || { echo 'VITO_SSH_ERROR: failed to cd to home directory' >&2; exit 1; }\nset -e; ".$command
    );

    expect($output)->toBe('');
    expect($executedCommand)->toBe($expected)->not->toContain("bash <<'EOF'");
})->with(["pwd\nls -la", "printf '%s' 'main#\nEOF\ntouch /tmp/ssh-escape\n#'"]);
