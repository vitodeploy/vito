<?php

namespace Tests\Feature\SSHCommands\Supervisor;

use App\SSHCommands\Supervisor\RestartWorkerCommand;
use Tests\TestCase;

class RestartWorkerCommandTest extends TestCase
{
    public function test_generate_command()
    {
        $command = new RestartWorkerCommand('1');

        $expected = <<<'EOD'
        if ! sudo supervisorctl restart 1:*; then
            echo 'VITO_SSH_ERROR' && exit 1
        fi
        EOD;

        $this->assertStringContainsString($expected, $command->content());
    }
}
