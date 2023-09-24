<?php

namespace Tests\Feature\SSHCommands\Storage;

use App\SSHCommands\Storage\UploadToFTPCommand;
use Tests\TestCase;

class UploadToFTPCommandTest extends TestCase
{
    public function test_generate_command()
    {
        $command = new UploadToFTPCommand(
            'src',
            'dest',
            '1.1.1.1',
            '21',
            'username',
            'password',
            true,
            true
        );

        $expected = <<<EOD
        curl --ftp-pasv -T "src" -u "username:password" ftps://1.1.1.1:21/dest
        EOD;

        $this->assertStringContainsString($expected, $command->content());
    }
}
