<?php

namespace Tests\Feature\SSHCommands\Database;

use App\SSHCommands\Database\BackupDatabaseCommand;
use Tests\TestCase;

class BackupDatabaseCommandTest extends TestCase
{
    public function test_generate_command()
    {
        $command = new BackupDatabaseCommand('mysql', 'test', 'test');

        $this->assertStringContainsString('sudo DEBIAN_FRONTEND=noninteractive mysqldump -u root test > test.sql;', $command->content());

        $this->assertStringContainsString('DEBIAN_FRONTEND=noninteractive zip test.zip test.sql;', $command->content());

        $this->assertStringContainsString('rm test.sql;', $command->content());
    }
}
