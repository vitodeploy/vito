<?php

namespace Tests\Feature\SSHCommands\Service;

use App\SSHCommands\Service\StartServiceCommand;
use Tests\TestCase;

class StartServiceCommandTest extends TestCase
{
    public function test_generate_command()
    {
        $command = new StartServiceCommand('nginx');

        $expected = <<<'EOD'
        sudo service nginx start

        sudo service nginx status | cat
        EOD;

        $this->assertStringContainsString($expected, $command->content());
    }
}
