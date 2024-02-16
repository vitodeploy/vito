<?php

namespace Tests\Unit\SSHCommands\PHPMyAdmin;

use App\SSHCommands\PHPMyAdmin\DeleteNginxPHPMyAdminVHostCommand;
use Tests\TestCase;

class DeleteNginxPHPMyAdminVHostCommandTest extends TestCase
{
    public function test_generate_command()
    {
        $command = new DeleteNginxPHPMyAdminVHostCommand('/home/vito/phpmyadmin');

        $expected = <<<'EOD'
        sudo rm -rf /home/vito/phpmyadmin

        sudo rm /etc/nginx/sites-available/phpmyadmin

        sudo rm /etc/nginx/sites-enabled/phpmyadmin

        if ! sudo service nginx restart; then
            echo 'VITO_SSH_ERROR' && exit 1
        fi

        echo "PHPMyAdmin deleted"
        EOD;

        $this->assertStringContainsString($expected, $command->content());
    }
}
