<?php

namespace Tests\Feature\SSHCommands\Website;

use App\SSHCommands\Website\CloneRepositoryCommand;
use Tests\TestCase;

class CloneRepositoryCommandTest extends TestCase
{
    public function test_generate_command()
    {
        $command = new CloneRepositoryCommand('git@github.com-key:vitodeploy/vito.git', 'path', 'main', 'pk_path');

        $expected = <<<EOD
        echo "Host github.com-pk_path
                Hostname github.com
                IdentityFile=~/.ssh/pk_path" >> ~/.ssh/config

        ssh-keyscan -H github.com >> ~/.ssh/known_hosts

        rm -rf path

        if ! git config --global core.fileMode false; then
            echo 'VITO_SSH_ERROR' && exit 1
        fi

        if ! git clone -b main git@github.com-key:vitodeploy/vito.git path; then
            echo 'VITO_SSH_ERROR' && exit 1
        fi

        if ! find path -type d -exec chmod 755 {} \;; then
            echo 'VITO_SSH_ERROR' && exit 1
        fi

        if ! find path -type f -exec chmod 644 {} \;; then
            echo 'VITO_SSH_ERROR' && exit 1
        fi

        if ! cd path && git config core.fileMode false; then
            echo 'VITO_SSH_ERROR' && exit 1
        fi
        EOD;

        $this->assertStringContainsString($expected, $command->content());
    }
}
