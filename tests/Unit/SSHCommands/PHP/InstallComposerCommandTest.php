<?php

namespace Tests\Unit\SSHCommands\PHP;

use App\SSHCommands\PHP\InstallComposerCommand;
use Tests\TestCase;

class InstallComposerCommandTest extends TestCase
{
    public function test_generate_command()
    {
        $command = new InstallComposerCommand();

        $expected = <<<'EOD'
        cd ~

        curl -sS https://getcomposer.org/installer -o composer-setup.php

        sudo php composer-setup.php --install-dir=/usr/local/bin --filename=composer

        composer
        EOD;

        $this->assertStringContainsString($expected, $command->content());
    }
}
