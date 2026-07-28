<?php

namespace Tests\Unit\Helpers;

use App\Exceptions\SSHCommandError;
use App\Facades\SSH;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SSHTest extends TestCase
{
    use RefreshDatabase;

    public function test_write_propagates_the_failure_and_cleans_up_the_local_temporary_file(): void
    {
        Storage::fake('local');

        $ssh = SSH::fake();
        $ssh->execWillFail();

        try {
            $this->server->ssh()->write('/home/vito/example.com/.env', 'APP_NAME=TestApp', 'vito');
            $this->fail('Expected the write to throw when the remote command fails.');
        } catch (SSHCommandError) {
            // expected
        }

        $this->assertEmpty(Storage::disk('local')->files());
    }

    public function test_write_cleans_up_the_local_temporary_file_on_success(): void
    {
        Storage::fake('local');

        SSH::fake();

        $this->server->ssh()->write('/home/vito/example.com/.env', 'APP_NAME=TestApp', 'vito');

        $this->assertEmpty(Storage::disk('local')->files());
    }

    public function test_write_removes_the_remote_temporary_file_on_both_branches(): void
    {
        SSH::fake();

        $this->server->ssh()->write('/home/vito/example.com/.env', 'APP_NAME=TestApp', 'vito');

        SSH::assertExecutedContains("> '/home/vito/example.com/.env'; then");
        SSH::assertExecutedContains("else\n    rm -f ");
        SSH::assertExecutedContains('exit 1');
    }

    public function test_write_quotes_the_destination_path(): void
    {
        SSH::fake();

        $this->server->ssh()->write('/home/vito/legacy path/.env', 'APP_NAME=TestApp', 'vito');

        SSH::assertExecutedContains("> '/home/vito/legacy path/.env'");
    }
}
