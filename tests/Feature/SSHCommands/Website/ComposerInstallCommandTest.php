<?php

namespace Tests\Feature\SSHCommands\Website;

use App\SSHCommands\Website\ComposerInstallCommand;
use Tests\TestCase;

class ComposerInstallCommandTest extends TestCase
{
    public function test_generate_command()
    {
        $command = new ComposerInstallCommand('path');

        $expected = <<<'EOD'
        if ! cd path; then
            echo 'VITO_SSH_ERROR' && exit 1
        fi

        if ! composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev; then
            echo 'VITO_SSH_ERROR' && exit 1
        fi
        EOD;

        $this->assertStringContainsString($expected, $command->content());
    }
}
