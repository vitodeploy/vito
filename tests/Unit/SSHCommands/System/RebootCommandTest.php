<?php

namespace Tests\Unit\SSHCommands\System;

use App\SSHCommands\System\RebootCommand;
use Tests\TestCase;

class RebootCommandTest extends TestCase
{
    public function test_generate_command()
    {
        $command = new RebootCommand();

        $expected = <<<'EOD'
        echo "Rebooting..."

        sudo reboot
        EOD;

        $this->assertStringContainsString($expected, $command->content());
    }
}
