<?php

namespace Tests\Unit\SSHCommands\SSL;

use App\SSHCommands\SSL\CreateLetsencryptSSLCommand;
use Tests\TestCase;

class CreateLetsencryptSSLCommandTest extends TestCase
{
    public function test_generate_command()
    {
        $command = new CreateLetsencryptSSLCommand('info@example.com', 'example.com', 'public');

        $expected = <<<'EOD'
        if ! sudo certbot certonly --force-renewal --nginx --noninteractive --agree-tos --cert-name example.com -m info@example.com -d example.com --verbose; then
            echo 'VITO_SSH_ERROR' && exit 1
        fi
        EOD;

        $this->assertStringContainsString($expected, $command->content());
    }
}
