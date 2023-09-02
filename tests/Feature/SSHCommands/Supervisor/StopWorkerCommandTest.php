<?php

namespace Tests\Feature\SSHCommands\Supervisor;

use App\SSHCommands\Supervisor\StopWorkerCommand;
use Tests\TestCase;

class StopWorkerCommandTest extends TestCase
{
    public function test_generate_command()
    {
        $command = new StopWorkerCommand('1');

        $expected = <<<'EOD'
        if ! sudo supervisorctl stop 1:*; then
            echo 'VITO_SSH_ERROR' && exit 1
        fi
        EOD;

        $this->assertStringContainsString($expected, $command->content());
    }
}
