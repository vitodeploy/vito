<?php

namespace Tests\Unit\SSHCommands\PHP;

use App\SSHCommands\PHP\GetPHPIniCommand;
use Tests\TestCase;

class GetPHPIniCommandTest extends TestCase
{
    public function test_generate_command()
    {
        $command = new GetPHPIniCommand('8.2');

        $expected = <<<'EOD'
        cat /etc/php/8.2/cli/php.ini
        EOD;

        $this->assertStringContainsString($expected, $command->content());
    }
}
