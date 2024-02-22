<?php

namespace Tests\Unit\SSHCommands\Nginx;

use App\SSHCommands\Nginx\UpdateNginxVHostCommand;
use Tests\TestCase;

class UpdateNginxVHostCommandTest extends TestCase
{
    public function test_generate_command()
    {
        $command = new UpdateNginxVHostCommand('example.com', '/home/vito/example.com', '__vhost__');

        $expected = <<<'EOD'
        if ! echo '__vhost__' | sudo tee /etc/nginx/sites-available/example.com; then
            echo 'VITO_SSH_ERROR' && exit 1
        fi

        if ! sudo service nginx restart; then
            echo 'VITO_SSH_ERROR' && exit 1
        fi
        EOD;

        $this->assertStringContainsString($expected, $command->content());
    }
}
