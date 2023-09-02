<?php

namespace Tests\Feature\SSHCommands\SSL;

use App\SSHCommands\SSL\CreateCustomSSLCommand;
use Tests\TestCase;

class CreateCustomSSLCommandTest extends TestCase
{
    public function test_generate_command()
    {
        $command = new CreateCustomSSLCommand('/home/vito/example.com', 'cert', 'pk', '/etc/cert', '/etc/pk');

        $expected = <<<'EOD'
        if ! sudo mkdir /home/vito/example.com; then
            echo 'VITO_SSH_ERROR' && exit 1
        fi

        if ! echo cert | sudo tee /etc/cert; then
            echo 'VITO_SSH_ERROR' && exit 1
        fi

        if ! echo pk | sudo tee /etc/pk; then
            echo 'VITO_SSH_ERROR' && exit 1
        fi

        echo "Successfully received certificate."
        EOD;

        $this->assertStringContainsString($expected, $command->content());
    }
}
