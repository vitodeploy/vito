<?php

namespace Tests\Unit\SSHCommands\PHPMyAdmin;

use App\SSHCommands\PHPMyAdmin\CreateNginxPHPMyAdminVHostCommand;
use Tests\TestCase;

class CreateNginxPHPMyAdminVHostCommandTest extends TestCase
{
    public function test_generate_command()
    {
        $command = new CreateNginxPHPMyAdminVHostCommand('__vhost__');

        $expected = <<<'EOD'
        if ! sudo chown -R 755 /home/vito/phpmyadmin; then
            echo 'VITO_SSH_ERROR' && exit 1
        fi

        if ! echo '__vhost__' | sudo tee /etc/nginx/sites-available/phpmyadmin; then
            echo 'VITO_SSH_ERROR' && exit 1
        fi

        if ! sudo ln -s /etc/nginx/sites-available/phpmyadmin /etc/nginx/sites-enabled/; then
            echo 'VITO_SSH_ERROR' && exit 1
        fi

        if ! sudo service nginx restart; then
            echo 'VITO_SSH_ERROR' && exit 1
        fi

        echo "PHPMyAdmin vhost created"
        EOD;

        $this->assertStringContainsString($expected, $command->content());
    }
}
