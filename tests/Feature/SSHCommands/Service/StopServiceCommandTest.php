<?php

namespace Tests\Feature\SSHCommands\Service;

use App\SSHCommands\Service\StopServiceCommand;
use Tests\TestCase;

class StopServiceCommandTest extends TestCase
{
    public function test_generate_command()
    {
        $command = new StopServiceCommand('nginx');

        $expected = <<<'EOD'
        sudo service nginx stop

        sudo service nginx status | cat
        EOD;

        $this->assertStringContainsString($expected, $command->content());
    }
}
