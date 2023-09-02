<?php

namespace Tests\Feature\SSHCommands\Storage;

use App\SSHCommands\Storage\UploadToDropboxCommand;
use Tests\TestCase;

class UploadToDropboxCommandTest extends TestCase
{
    public function test_generate_command()
    {
        $command = new UploadToDropboxCommand('src', 'dest', 'token');

        $expected = <<<EOD
        curl -sb --location --request POST 'https://content.dropboxapi.com/2/files/upload' \
        --header 'Accept: application/json' \
        --header 'Dropbox-API-Arg: {"path":"dest"}' \
        --header 'Content-Type: text/plain; charset=dropbox-cors-hack' \
        --header 'Authorization: Bearer token' \
        --data-binary '@src'
        EOD;

        $this->assertStringContainsString($expected, $command->content());
    }
}
