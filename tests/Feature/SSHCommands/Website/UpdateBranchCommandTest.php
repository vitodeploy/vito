<?php

namespace Tests\Feature\SSHCommands\Website;

use App\SSHCommands\Website\UpdateBranchCommand;
use Tests\TestCase;

class UpdateBranchCommandTest extends TestCase
{
    public function test_generate_command()
    {
        $command = new UpdateBranchCommand('path', 'main');

        $expected = <<<'EOD'
        if ! cd path; then
            echo 'VITO_SSH_ERROR' && exit 1
        fi

        if ! git checkout -f main; then
            echo 'VITO_SSH_ERROR' && exit 1
        fi
        EOD;

        $this->assertStringContainsString($expected, $command->content());
    }
}
