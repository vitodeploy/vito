<?php

namespace Tests\Feature\SSHCommands\Nginx;

use App\SSHCommands\Nginx\DeleteNginxSiteCommand;
use Tests\TestCase;

class DeleteNginxSiteCommandTest extends TestCase
{
    public function test_generate_command()
    {
        $command = new DeleteNginxSiteCommand('example.com', '/home/vito/example.com');

        $expected = <<<'EOD'
        rm -rf /home/vito/example.com

        sudo rm /etc/nginx/sites-available/example.com

        sudo rm /etc/nginx/sites-enabled/example.com

        echo "Site deleted"
        EOD;

        $this->assertStringContainsString($expected, $command->content());
    }
}
