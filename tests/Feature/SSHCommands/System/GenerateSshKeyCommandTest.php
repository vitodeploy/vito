<?php

namespace Tests\Feature\SSHCommands\System;

use App\SSHCommands\System\GenerateSshKeyCommand;
use Tests\TestCase;

class GenerateSshKeyCommandTest extends TestCase
{
    public function test_generate_command()
    {
        $command = new GenerateSshKeyCommand('key');

        $expected = <<<'EOD'
        ssh-keygen -t rsa -b 4096 -N "" -f ~/.ssh/key
        EOD;

        $this->assertStringContainsString($expected, $command->content());
    }
}
