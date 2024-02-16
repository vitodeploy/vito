<?php

namespace Tests\Unit\SSHCommands\Storage;

use App\SSHCommands\Storage\DownloadFromDropboxCommand;
use Tests\TestCase;

class DownloadFromDropboxCommandTest extends TestCase
{
    public function test_generate_command()
    {
        $command = new DownloadFromDropboxCommand('src', 'dest', 'token');

        $expected = <<<EOD
        curl -o dest --location --request POST 'https://content.dropboxapi.com/2/files/download' \
        --header 'Accept: application/json' \
        --header 'Dropbox-API-Arg: {"path":"src"}' \
        --header 'Authorization: Bearer token'
        EOD;

        $this->assertStringContainsString($expected, $command->content());
    }
}
