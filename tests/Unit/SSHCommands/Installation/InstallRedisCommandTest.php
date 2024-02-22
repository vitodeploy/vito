<?php

namespace Tests\Unit\SSHCommands\Installation;

use App\SSHCommands\Installation\InstallRedisCommand;
use Tests\TestCase;

class InstallRedisCommandTest extends TestCase
{
    public function test_generate_command()
    {
        $command = new InstallRedisCommand();

        $expected = <<<'EOD'
        sudo DEBIAN_FRONTEND=noninteractive apt install redis-server -y

        sudo sed -i 's/bind 127.0.0.1 ::1/bind 0.0.0.0/g' /etc/redis/redis.conf

        sudo service redis enable

        sudo service redis start
        EOD;

        $this->assertStringContainsString($expected, $command->content());
    }
}
