<?php

namespace Tests\Feature\SSHCommands\System;

use App\SSHCommands\System\DeploySshKeyCommand;
use Tests\TestCase;

class DeploySshKeyCommandTest extends TestCase
{
    public function test_generate_command()
    {
        $command = new DeploySshKeyCommand('key');

        $expected = <<<'EOD'
        if ! echo 'key' | sudo tee -a ~/.ssh/authorized_keys; then
            echo 'VITO_SSH_ERROR' && exit 1
        fi
        EOD;

        $this->assertStringContainsString($expected, $command->content());
    }
}
