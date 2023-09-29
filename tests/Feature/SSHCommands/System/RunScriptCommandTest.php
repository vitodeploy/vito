<?php

namespace Tests\Feature\SSHCommands\System;

use App\SSHCommands\System\RunScriptCommand;
use Tests\TestCase;

class RunScriptCommandTest extends TestCase
{
    public function test_generate_command()
    {
        $command = new RunScriptCommand('/path', 'script');

        $expected = <<<'EOD'
        if ! cd /path; then
            echo 'VITO_SSH_ERROR' && exit 1
        fi

        script
        EOD;

        $this->assertStringContainsString($expected, $command->content());
    }
}
