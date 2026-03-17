<?php

namespace Tests\Unit\Models;

use App\Enums\ServerStatus;
use App\Facades\SSH;
use App\Helpers\SSH as SSHHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use phpseclib3\Net\SSH2;
use ReflectionProperty;
use Tests\TestCase;

class ServerModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_have_default_service(): void
    {
        $php = $this->server->defaultService('php');
        $php->update(['is_default' => false]);
        $this->assertNotNull($this->server->defaultService('php'));
        $php->refresh();
        $this->assertTrue($php->is_default);
    }

    public function test_check_connection_is_ready(): void
    {
        SSH::fake();

        $this->server->update(['status' => ServerStatus::DISCONNECTED]);

        $this->server->checkConnection();

        $this->assertDatabaseHas('servers', [
            'id' => $this->server->id,
            'status' => ServerStatus::READY,
        ]);
    }

    public function test_connection_failed(): void
    {
        SSH::fake()->connectionWillFail();

        $this->server->update(['status' => ServerStatus::READY]);

        $this->server->checkConnection();

        $this->assertDatabaseHas('servers', [
            'id' => $this->server->id,
            'status' => ServerStatus::DISCONNECTED,
        ]);
    }

    public function test_exec_wraps_command_when_using_custom_user(): void
    {
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
                $this->isType('string'),
                $this->isType('callable')
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
        $reflection->setAccessible(true);
        $reflection->setValue($ssh, $connection);

        $command = <<<'BASH'
pwd
ls -la
BASH;

        $output = $ssh->exec($command);
        $ssh->disconnect();

        $expected = <<<'BASH'
sudo -u deploy bash <<'EOF'
cd ~ || { echo 'VITO_SSH_ERROR: failed to cd to home directory' >&2; exit 1; }
pwd
ls -la
EOF
BASH;

        $this->assertSame('', $output);
        $this->assertSame($expected, $executedCommand);
    }
}
