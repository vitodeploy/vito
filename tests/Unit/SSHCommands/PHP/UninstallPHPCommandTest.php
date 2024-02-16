<?php

namespace Tests\Unit\SSHCommands\PHP;

use App\SSHCommands\PHP\UninstallPHPCommand;
use Tests\TestCase;

class UninstallPHPCommandTest extends TestCase
{
    public function test_generate_command()
    {
        $command = new UninstallPHPCommand('8.1');

        $expected = <<<'EOD'
        sudo service php8.1-fpm stop

        if ! sudo DEBIAN_FRONTEND=noninteractive apt remove -y php8.1 php8.1-fpm php8.1-mbstring php8.1-mysql php8.1-mcrypt php8.1-gd php8.1-xml php8.1-curl php8.1-gettext php8.1-zip php8.1-bcmath php8.1-soap php8.1-redis; then
            echo 'VITO_SSH_ERROR' && exit 1
        fi
        EOD;

        $this->assertStringContainsString($expected, $command->content());
    }
}
