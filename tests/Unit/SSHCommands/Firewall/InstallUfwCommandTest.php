<?php

namespace Tests\Unit\SSHCommands\Firewall;

use App\SSHCommands\Firewall\InstallUfwCommand;
use Tests\TestCase;

class InstallUfwCommandTest extends TestCase
{
    public function test_generate_command()
    {
        $command = new InstallUfwCommand();

        $expected = <<<'EOD'
        if ! sudo ufw default deny incoming; then
            echo 'VITO_SSH_ERROR' && exit 1
        fi

        if ! sudo ufw default allow outgoing; then
            echo 'VITO_SSH_ERROR' && exit 1
        fi

        if ! sudo ufw allow from 0.0.0.0/0 to any proto tcp port 22; then
            echo 'VITO_SSH_ERROR' && exit 1
        fi

        if ! sudo ufw allow from 0.0.0.0/0 to any proto tcp port 80; then
            echo 'VITO_SSH_ERROR' && exit 1
        fi

        if ! sudo ufw allow from 0.0.0.0/0 to any proto tcp port 443; then
            echo 'VITO_SSH_ERROR' && exit 1
        fi

        if ! sudo ufw --force enable; then
            echo 'VITO_SSH_ERROR' && exit 1
        fi

        if ! sudo ufw reload; then
            echo 'VITO_SSH_ERROR' && exit 1
        fi
        EOD;

        $this->assertStringContainsString($expected, $command->content());
    }
}
