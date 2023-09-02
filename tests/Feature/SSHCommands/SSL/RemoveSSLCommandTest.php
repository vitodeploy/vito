<?php

namespace Tests\Feature\SSHCommands\SSL;

use App\SSHCommands\SSL\RemoveSSLCommand;
use Tests\TestCase;

class RemoveSSLCommandTest extends TestCase
{
    public function test_generate_command()
    {
        $command = new RemoveSSLCommand('/etc/letsencrypt/');

        $expected = <<<'EOD'
        sudo rm -rf /etc/letsencrypt/*
        EOD;

        $this->assertStringContainsString($expected, $command->content());
    }
}
