<?php

namespace Tests\Unit\SSHCommands\PHP;

use App\SSHCommands\PHP\InstallPHPCommand;
use Tests\TestCase;

class InstallPHPCommandTest extends TestCase
{
    public function test_generate_command()
    {
        $command = new InstallPHPCommand('8.1');

        $expected = <<<'EOD'
        sudo add-apt-repository ppa:ondrej/php -y

        sudo DEBIAN_FRONTEND=noninteractive apt update

        sudo DEBIAN_FRONTEND=noninteractive apt install -y php8.1 php8.1-fpm php8.1-mbstring php8.1-mysql php8.1-mcrypt php8.1-gd php8.1-xml php8.1-curl php8.1-gettext php8.1-zip php8.1-bcmath php8.1-soap php8.1-redis

        if ! sudo sed -i 's/www-data/vito/g' /etc/php/8.1/fpm/pool.d/www.conf; then
            echo 'VITO_SSH_ERROR' && exit 1
        fi

        sudo service php8.1-fpm enable

        sudo service php8.1-fpm start
        EOD;

        $this->assertStringContainsString($expected, $command->content());
    }
}
