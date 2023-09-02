<?php

namespace Tests\Feature\SSHCommands\System;

use App\SSHCommands\System\EditFileCommand;
use Tests\TestCase;

class EditFileCommandTest extends TestCase
{
    public function test_generate_command()
    {
        $command = new EditFileCommand('/path/to/file', 'content');

        $expected = <<<'EOD'
        if ! echo "content" | tee /path/to/file; then
            echo 'VITO_SSH_ERROR' && exit 1
        fi
        EOD;

        $this->assertStringContainsString($expected, $command->content());
    }
}
