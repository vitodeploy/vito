<?php

namespace Tests\Feature\SSHCommands\Database;

use App\SSHCommands\Database\CreateCommand;
use Tests\TestCase;

class CreateCommandTest extends TestCase
{
    public function test_generate_command()
    {
        $command = new CreateCommand('mysql', 'test');

        $this->assertStringContainsString('sudo mysql -e "CREATE DATABASE IF NOT EXISTS test CHARACTER SET utf8 COLLATE utf8_general_ci";', $command->content());

        $this->assertStringContainsString('echo "Command executed"', $command->content());
    }
}
