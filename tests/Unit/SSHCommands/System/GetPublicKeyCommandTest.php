<?php

namespace Tests\Unit\SSHCommands\System;

use App\SSHCommands\System\GetPublicKeyCommand;
use Tests\TestCase;

class GetPublicKeyCommandTest extends TestCase
{
    public function test_generate_command()
    {
        $command = new GetPublicKeyCommand();

        $expected = <<<'EOD'
        cat ~/.ssh/id_rsa.pub
        EOD;

        $this->assertStringContainsString($expected, $command->content());
    }
}
