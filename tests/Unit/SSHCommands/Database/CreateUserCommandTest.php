<?php

namespace Tests\Unit\SSHCommands\Database;

use App\SSHCommands\Database\CreateUserCommand;
use Tests\TestCase;

class CreateUserCommandTest extends TestCase
{
    public function test_generate_command()
    {
        $command = new CreateUserCommand('mysql', 'user', 'password', '%');

        $this->assertStringContainsString('sudo mysql -e "CREATE USER IF NOT EXISTS \'user\'@\'%\' IDENTIFIED BY \'password\'";', $command->content());

        $this->assertStringContainsString('sudo mysql -e "FLUSH PRIVILEGES";', $command->content());
    }
}
