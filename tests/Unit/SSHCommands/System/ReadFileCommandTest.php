<?php

namespace Tests\Unit\SSHCommands\System;

use App\SSHCommands\System\ReadFileCommand;
use Tests\TestCase;

class ReadFileCommandTest extends TestCase
{
    public function test_generate_command()
    {
        $command = new ReadFileCommand('/path');

        $expected = <<<'EOD'
        cat /path;
        EOD;

        $this->assertStringContainsString($expected, $command->content());
    }
}
