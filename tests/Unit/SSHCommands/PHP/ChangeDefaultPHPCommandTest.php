<?php

namespace Tests\Unit\SSHCommands\PHP;

use App\SSHCommands\PHP\ChangeDefaultPHPCommand;
use Tests\TestCase;

class ChangeDefaultPHPCommandTest extends TestCase
{
    public function test_generate_command()
    {
        $command = new ChangeDefaultPHPCommand('8.2');

        $expected = <<<'EOD'
        if ! sudo rm /usr/bin/php; then
            echo 'VITO_SSH_ERROR' && exit 1
        fi

        if ! sudo ln -s /usr/bin/php8.2 /usr/bin/php; then
            echo 'VITO_SSH_ERROR' && exit 1
        fi

        echo "Default php is: "

        php -v
        EOD;

        $this->assertStringContainsString($expected, $command->content());
    }
}
