<?php

namespace Tests\Unit\SSHCommands\Installation;

use App\SSHCommands\Installation\InstallNodejsCommand;
use Tests\TestCase;

class InstallNodejsCommandTest extends TestCase
{
    public function test_generate_command()
    {
        $command = new InstallNodejsCommand();

        $expected = <<<'EOD'
        curl -fsSL https://deb.nodesource.com/setup_lts.x | sudo -E bash -;

        sudo DEBIAN_FRONTEND=noninteractive apt update

        sudo DEBIAN_FRONTEND=noninteractive apt install nodejs -y
        EOD;

        $this->assertStringContainsString($expected, $command->content());
    }
}
