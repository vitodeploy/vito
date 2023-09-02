<?php

namespace Tests\Feature\SSHCommands\PHP;

use App\SSHCommands\PHP\InstallPHPExtensionCommand;
use Tests\TestCase;

class InstallPHPExtensionCommandTest extends TestCase
{
    public function test_generate_command()
    {
        $command = new InstallPHPExtensionCommand('8.1', 'imagick');

        $expected = <<<'EOD'
        sudo apt install -y php8.1-imagick

        sudo service php8.1-fpm restart

        php8.1 -m
        EOD;

        $this->assertStringContainsString($expected, $command->content());
    }
}
