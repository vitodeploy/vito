<?php

namespace Tests\Unit\SSHCommands\Database;

use App\SSHCommands\Database\UnlinkCommand;
use Tests\TestCase;

class UnlinkCommandTest extends TestCase
{
    public function test_generate_command()
    {
        $command = new UnlinkCommand('mysql', 'user', '%');

        $expected = <<<'EOD'
        if ! sudo mysql -e "REVOKE ALL PRIVILEGES, GRANT OPTION FROM 'user'@'%'"; then
            echo 'VITO_SSH_ERROR' && exit 1
        fi

        echo "Command executed"
        EOD;

        $this->assertStringContainsString($expected, $command->content());
    }
}
