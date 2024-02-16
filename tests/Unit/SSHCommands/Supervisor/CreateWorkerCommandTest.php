<?php

namespace Tests\Unit\SSHCommands\Supervisor;

use App\SSHCommands\Supervisor\CreateWorkerCommand;
use Tests\TestCase;

class CreateWorkerCommandTest extends TestCase
{
    public function test_generate_command()
    {
        $command = new CreateWorkerCommand('1', 'config');

        $expected = <<<'EOD'
        mkdir -p ~/.logs

        mkdir -p ~/.logs/workers

        touch ~/.logs/workers/1.log

        if ! echo 'config' | sudo tee /etc/supervisor/conf.d/1.conf; then
            echo 'VITO_SSH_ERROR' && exit 1
        fi

        if ! sudo supervisorctl reread; then
            echo 'VITO_SSH_ERROR' && exit 1
        fi

        if ! sudo supervisorctl update; then
            echo 'VITO_SSH_ERROR' && exit 1
        fi

        if ! sudo supervisorctl start 1:*; then
            echo 'VITO_SSH_ERROR' && exit 1
        fi
        EOD;

        $this->assertStringContainsString($expected, $command->content());
    }
}
