<?php

namespace Tests\Unit\SSHCommands\Supervisor;

use App\SSHCommands\Supervisor\StartWorkerCommand;
use Tests\TestCase;

class StartWorkerCommandTest extends TestCase
{
    public function test_generate_command()
    {
        $command = new StartWorkerCommand('1');

        $expected = <<<'EOD'
        if ! sudo supervisorctl start 1:*; then
            echo 'VITO_SSH_ERROR' && exit 1
        fi
        EOD;

        $this->assertStringContainsString($expected, $command->content());
    }
}
