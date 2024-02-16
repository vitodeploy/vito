<?php

namespace Tests\Unit\SSHCommands\System;

use App\SSHCommands\System\ReadSshKeyCommand;
use Tests\TestCase;

class ReadSshKeyCommandTest extends TestCase
{
    public function test_generate_command()
    {
        $command = new ReadSshKeyCommand('name');

        $expected = <<<'EOD'
        cat ~/.ssh/name.pub
        EOD;

        $this->assertStringContainsString($expected, $command->content());
    }
}
