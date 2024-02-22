<?php

namespace Tests\Unit\SSHCommands\Nginx;

use App\SSHCommands\Nginx\UpdateNginxRedirectsCommand;
use Tests\TestCase;

class UpdateNginxRedirectsCommandTest extends TestCase
{
    public function test_generate_command()
    {
        $command = new UpdateNginxRedirectsCommand('example.com', '__redirects__');

        $expected = <<<'EOD'
        if ! echo '__redirects__' | sudo tee /etc/nginx/conf.d/example.com_redirects; then
            echo 'VITO_SSH_ERROR' && exit 1
        fi

        if ! sudo service nginx restart; then
            echo 'VITO_SSH_ERROR' && exit 1
        fi
        EOD;

        $this->assertStringContainsString($expected, $command->content());
    }
}
