<?php

namespace Tests\Feature\SSHCommands\Nginx;

use App\SSHCommands\Nginx\ChangeNginxPHPVersionCommand;
use Tests\TestCase;

class ChangeNginxPHPVersionCommandTest extends TestCase
{
    public function test_generate_command()
    {
        $command = new ChangeNginxPHPVersionCommand('example.com', '7.1', '8.2');

        $expected = <<<'EOD'
        if ! sudo sed -i 's/php7.1/php8.2/g' /etc/nginx/sites-available/example.com; then
            echo 'VITO_SSH_ERROR' && exit 1
        fi

        if ! sudo service nginx restart; then
            echo 'VITO_SSH_ERROR' && exit 1
        fi

        echo "PHP Version Changed to 8.2"
        EOD;

        $this->assertStringContainsString($expected, $command->content());
    }
}
