<?php

namespace Tests\Feature\SSHCommands\Storage;

use App\SSHCommands\Storage\DownloadFromDropboxCommand;
use App\SSHCommands\Storage\DownloadFromFTPCommand;
use Tests\TestCase;

class DownloadFromFTPCommandTest extends TestCase
{
    public function test_generate_command()
    {
        $command = new DownloadFromFTPCommand(
            'src',
            'dest',
            '1.1.1.1',
            '21',
            'username',
            'password',
            false,
            true,
        );

        $expected = <<<EOD
        curl --ftp-pasv -u "username:password" ftp://1.1.1.1:21/src -o "dest"
        EOD;

        $this->assertStringContainsString($expected, $command->content());
    }
}
