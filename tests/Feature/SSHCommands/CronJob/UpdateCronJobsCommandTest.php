<?php

namespace Tests\Feature\SSHCommands\CronJob;

use App\SSHCommands\CronJob\UpdateCronJobsCommand;
use Tests\TestCase;

class UpdateCronJobsCommandTest extends TestCase
{
    public function test_generate_command()
    {
        $command = new UpdateCronJobsCommand('vito', 'ls -la');

        $this->assertStringContainsString("echo 'ls -la' | sudo -u vito crontab -;", $command->content());

        $this->assertStringContainsString('sudo -u vito crontab -l;', $command->content());
    }
}
