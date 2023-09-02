<?php

namespace Tests\Feature\SSHCommands\Supervisor;

use App\SSHCommands\Supervisor\InstallSupervisorCommand;
use Tests\TestCase;

class InstallSupervisorCommandTest extends TestCase
{
    public function test_generate_command()
    {
        $command = new InstallSupervisorCommand();

        $expected = <<<'EOD'
        sudo DEBIAN_FRONTEND=noninteractive apt-get install supervisor -y

        sudo service supervisor enable

        sudo service supervisor start
        EOD;

        $this->assertStringContainsString($expected, $command->content());
    }
}
