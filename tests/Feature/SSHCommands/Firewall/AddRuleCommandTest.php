<?php

namespace Tests\Feature\SSHCommands\Firewall;

use App\SSHCommands\Firewall\AddRuleCommand;
use Tests\TestCase;

class AddRuleCommandTest extends TestCase
{
    public function test_generate_command()
    {
        $command = new AddRuleCommand('ufw', 'allow', 'tcp', '1080', '0.0.0.0', '0');

        $expected = <<<'EOD'
        if ! sudo ufw allow from 0.0.0.0/0 to any proto tcp port 1080; then
            echo 'VITO_SSH_ERROR' && exit 1
        fi

        if ! sudo ufw reload; then
            echo 'VITO_SSH_ERROR' && exit 1
        fi

        if ! sudo service ufw restart; then
            echo 'VITO_SSH_ERROR' && exit 1
        fi
        EOD;

        $this->assertStringContainsString($expected, $command->content());
    }
}
