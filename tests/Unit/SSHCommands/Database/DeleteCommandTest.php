<?php

namespace Tests\Unit\SSHCommands\Database;

use App\SSHCommands\Database\DeleteCommand;
use Tests\TestCase;

class DeleteCommandTest extends TestCase
{
    public function test_generate_command()
    {
        $command = new DeleteCommand('mysql', 'test');

        $this->assertStringContainsString('sudo mysql -e "DROP DATABASE IF EXISTS test";', $command->content());
    }
}
