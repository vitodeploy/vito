<?php

namespace Tests\Unit\SSHCommands\SSL;

use App\SSHCommands\SSL\InstallCertbotCommand;
use Tests\TestCase;

class InstallCertbotCommandTest extends TestCase
{
    public function test_generate_command()
    {
        $command = new InstallCertbotCommand();

        $expected = <<<'EOD'
        sudo DEBIAN_FRONTEND=noninteractive apt install certbot python3-certbot-nginx -y
        EOD;

        $this->assertStringContainsString($expected, $command->content());
    }
}
