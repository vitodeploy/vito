<?php

namespace Tests\Unit\SSHCommands\System;

use App\SSHCommands\System\DeleteSshKeyCommand;
use Tests\TestCase;

class DeleteSshKeyCommandTest extends TestCase
{
    public function test_generate_command()
    {
        $command = new DeleteSshKeyCommand('key');

        $expected = <<<'EOD'
        sudo sed -i 's/key//g' ~/.ssh/authorized_keys
        EOD;

        $this->assertStringContainsString($expected, $command->content());
    }
}
