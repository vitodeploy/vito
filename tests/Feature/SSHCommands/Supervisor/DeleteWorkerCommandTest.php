<?php

namespace Tests\Feature\SSHCommands\Supervisor;

use App\SSHCommands\Supervisor\DeleteWorkerCommand;
use Tests\TestCase;

class DeleteWorkerCommandTest extends TestCase
{
    public function test_generate_command()
    {
        $command = new DeleteWorkerCommand('1');

        $expected = <<<'EOD'
        if ! sudo supervisorctl stop 1:*; then
            echo 'VITO_SSH_ERROR' && exit 1
        fi

        if ! sudo rm -rf ~/.logs/workers/1.log; then
            echo 'VITO_SSH_ERROR' && exit 1
        fi

        if ! sudo rm -rf /etc/supervisor/conf.d/1.conf; then
            echo 'VITO_SSH_ERROR' && exit 1
        fi

        if ! sudo supervisorctl reread; then
            echo 'VITO_SSH_ERROR' && exit 1
        fi

        if ! sudo supervisorctl update; then
            echo 'VITO_SSH_ERROR' && exit 1
        fi
        EOD;

        $this->assertStringContainsString($expected, $command->content());
    }
}
