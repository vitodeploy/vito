<?php

namespace Tests\Feature\SSHCommands\Nginx;

use App\SSHCommands\Nginx\CreateNginxVHostCommand;
use Tests\TestCase;

class CreateNginxVHostCommandTest extends TestCase
{
    public function test_generate_command()
    {
        $command = new CreateNginxVHostCommand('example.com', '/home/vito/example.com', '__the__vhost__');

        $expected = <<<'EOD'
        if ! rm -rf /home/vito/example.com; then
            echo 'VITO_SSH_ERROR' && exit 1
        fi

        if ! mkdir /home/vito/example.com; then
            echo 'VITO_SSH_ERROR' && exit 1
        fi

        if ! sudo chown -R 755 /home/vito/example.com; then
            echo 'VITO_SSH_ERROR' && exit 1
        fi

        if ! echo '' | sudo tee /etc/nginx/conf.d/example.com_redirects; then
            echo 'VITO_SSH_ERROR' && exit 1
        fi

        if ! echo '__the__vhost__' | sudo tee /etc/nginx/sites-available/example.com; then
            echo 'VITO_SSH_ERROR' && exit 1
        fi

        if ! sudo ln -s /etc/nginx/sites-available/example.com /etc/nginx/sites-enabled/; then
            echo 'VITO_SSH_ERROR' && exit 1
        fi

        if ! sudo service nginx restart; then
            echo 'VITO_SSH_ERROR' && exit 1
        fi
        EOD;

        $this->assertStringContainsString($expected, $command->content());
    }
}
