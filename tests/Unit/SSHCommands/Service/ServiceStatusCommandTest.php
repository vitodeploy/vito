<?php

namespace Tests\Unit\SSHCommands\Service;

use App\SSHCommands\Service\ServiceStatusCommand;
use Tests\TestCase;

class ServiceStatusCommandTest extends TestCase
{
    public function test_generate_command()
    {
        $command = new ServiceStatusCommand('nginx');

        $expected = <<<'EOD'
        sudo service nginx status | cat
        EOD;

        $this->assertStringContainsString($expected, $command->content());
    }
}
