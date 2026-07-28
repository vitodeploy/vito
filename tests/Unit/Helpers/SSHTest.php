<?php

namespace Tests\Unit\Helpers;

use App\Facades\SSH;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SSHTest extends TestCase
{
    use RefreshDatabase;

    public function test_write_removes_the_temporary_file_on_both_the_success_and_failure_branches(): void
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
