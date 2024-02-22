<?php

namespace Tests\Unit\SSHCommands\Installation;

use App\SSHCommands\Installation\InstallRequirementsCommand;
use Tests\TestCase;

class InstallRequirementsCommandTest extends TestCase
{
    public function test_generate_command()
    {
        $command = new InstallRequirementsCommand('user@example.com', 'user');

        $expected = <<<'EOD'
        sudo DEBIAN_FRONTEND=noninteractive apt install -y software-properties-common curl zip unzip git gcc
        git config --global user.email "user@example.com"
        git config --global user.name "user"
        EOD;

        $this->assertStringContainsString($expected, $command->content());
    }
}
