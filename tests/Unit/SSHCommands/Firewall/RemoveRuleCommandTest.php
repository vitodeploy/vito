<?php

namespace Tests\Unit\SSHCommands\Firewall;

use App\SSHCommands\Firewall\RemoveRuleCommand;
use Tests\TestCase;

class RemoveRuleCommandTest extends TestCase
{
    public function test_generate_command()
    {
        $command = new RemoveRuleCommand('ufw', 'allow', 'tcp', '1080', '0.0.0.0', '0');

        $expected = <<<'EOD'
        if ! sudo ufw delete allow from 0.0.0.0/0 to any proto tcp port 1080; then
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
