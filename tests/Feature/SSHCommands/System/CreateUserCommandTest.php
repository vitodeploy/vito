<?php

namespace Tests\Feature\SSHCommands\System;

use App\SSHCommands\System\CreateUserCommand;
use Tests\TestCase;

class CreateUserCommandTest extends TestCase
{
    public function test_generate_command()
    {
        $command = new CreateUserCommand('vito', 'password', 'key');

        $expected = <<<'EOD'
        export DEBIAN_FRONTEND=noninteractive
        echo "key" | sudo tee -a /home/root/.ssh/authorized_keys
        sudo useradd -p $(openssl passwd -1 password) vito
        sudo usermod -aG sudo vito
        echo "vito ALL=(ALL) NOPASSWD:ALL" | sudo tee -a /etc/sudoers
        sudo mkdir /home/vito
        sudo mkdir /home/vito/.ssh
        echo "key" | sudo tee -a /home/vito/.ssh/authorized_keys
        sudo chown -R vito:vito /home/vito
        sudo chsh -s /bin/bash vito
        sudo su - vito -c "ssh-keygen -t rsa -N '' -f ~/.ssh/id_rsa" <<< y
        EOD;

        $this->assertStringContainsString($expected, $command->content());
    }
}
