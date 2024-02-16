<?php

namespace Tests\Unit\SSHCommands\Database;

use App\SSHCommands\Database\LinkCommand;
use Tests\TestCase;

class LinkCommandTest extends TestCase
{
    public function test_generate_command()
    {
        $command = new LinkCommand('mysql', 'user', '%', 'test');

        $expected = <<<'EOD'
        if ! sudo mysql -e "GRANT ALL PRIVILEGES ON test.* TO 'user'@'%'"; then
            echo 'VITO_SSH_ERROR' && exit 1
        fi

        if ! sudo mysql -e "FLUSH PRIVILEGES"; then
            echo 'VITO_SSH_ERROR' && exit 1
        fi

        echo "Linking to test finished"
        EOD;

        $this->assertStringContainsString($expected, $command->content());
    }
}
