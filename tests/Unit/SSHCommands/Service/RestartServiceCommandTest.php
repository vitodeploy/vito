<?php

namespace Tests\Unit\SSHCommands\Service;

use App\SSHCommands\Service\RestartServiceCommand;
use Tests\TestCase;

class RestartServiceCommandTest extends TestCase
{
    public function test_generate_command()
    {
        $command = new RestartServiceCommand('nginx');

        $expected = <<<'EOD'
        sudo service nginx restart

        sudo service nginx status | cat
        EOD;

        $this->assertStringContainsString($expected, $command->content());
    }
}
