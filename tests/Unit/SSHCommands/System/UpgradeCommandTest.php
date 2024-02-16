<?php

namespace Tests\Unit\SSHCommands\System;

use App\SSHCommands\System\UpgradeCommand;
use Tests\TestCase;

class UpgradeCommandTest extends TestCase
{
    public function test_generate_command()
    {
        $command = new UpgradeCommand();

        $expected = <<<'EOD'
        sudo DEBIAN_FRONTEND=noninteractive apt clean

        sudo DEBIAN_FRONTEND=noninteractive apt update

        sudo DEBIAN_FRONTEND=noninteractive apt upgrade -y

        sudo DEBIAN_FRONTEND=noninteractive apt autoremove -y
        EOD;

        $this->assertStringContainsString($expected, $command->content());
    }
}
