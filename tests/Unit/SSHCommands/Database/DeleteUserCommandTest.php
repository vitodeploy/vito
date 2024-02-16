<?php

namespace Tests\Unit\SSHCommands\Database;

use App\SSHCommands\Database\DeleteUserCommand;
use Tests\TestCase;

class DeleteUserCommandTest extends TestCase
{
    public function test_generate_command()
    {
        $command = new DeleteUserCommand('mysql', 'user', '%');

        $this->assertStringContainsString('sudo mysql -e "DROP USER IF EXISTS \'user\'@\'%\'";', $command->content());

        $this->assertStringContainsString('sudo mysql -e "FLUSH PRIVILEGES";', $command->content());
    }
}
