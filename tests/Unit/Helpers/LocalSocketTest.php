<?php

namespace Tests\Unit\Helpers;

use App\Contracts\ServerConnection;
use App\Facades\SSH;
use App\Helpers\LocalSocket;
use App\Helpers\SSH as SSHHelper;
use App\Models\Server;
use App\Support\Testing\LocalSocketFake;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalSocketTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_socket_implements_remote_connection_interface(): void
    {
        $localSocket = new LocalSocket;

        $this->assertInstanceOf(ServerConnection::class, $localSocket);
    }

    public function test_ssh_implements_remote_connection_interface(): void
    {
        $ssh = new SSHHelper;

        $this->assertInstanceOf(ServerConnection::class, $ssh);
    }

    public function test_facade_returns_local_socket_for_local_server(): void
    {
        $localServer = Server::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
            'is_local' => true,
        ]);

        $connection = SSH::init($localServer);

        $this->assertInstanceOf(LocalSocket::class, $connection);
    }

    public function test_facade_returns_ssh_for_remote_server(): void
    {
        // Use the server from TestCase which has SSH keys set up
        $this->assertFalse($this->server->is_local);

        $connection = SSH::init($this->server);

        $this->assertInstanceOf(SSHHelper::class, $connection);
    }

    public function test_local_socket_init_sets_server(): void
    {
        $localServer = Server::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
            'is_local' => true,
        ]);

        $localSocket = new LocalSocket;
        $localSocket->init($localServer);

        $this->assertEquals($localServer->id, $localSocket->server->id);
    }

    public function test_local_socket_init_with_as_user(): void
    {
        $localServer = Server::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
            'is_local' => true,
        ]);

        $localSocket = new LocalSocket;
        $result = $localSocket->init($localServer, 'www-data');

        $this->assertInstanceOf(LocalSocket::class, $result);
    }

    public function test_local_socket_set_log(): void
    {
        $localServer = Server::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
            'is_local' => true,
        ]);

        $localSocket = new LocalSocket;
        $localSocket->init($localServer);

        $result = $localSocket->setLog(null);

        $this->assertInstanceOf(LocalSocket::class, $result);
    }

    public function test_local_socket_use_log(): void
    {
        $localServer = Server::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
            'is_local' => true,
        ]);

        $localSocket = new LocalSocket;
        $localSocket->init($localServer);

        $result = $localSocket->useLog('local', 'test.log');

        $this->assertInstanceOf(LocalSocket::class, $result);
    }

    public function test_local_socket_as_user(): void
    {
        $localServer = Server::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
            'is_local' => true,
        ]);

        $localSocket = new LocalSocket;
        $localSocket->init($localServer);

        $result = $localSocket->asUser('www-data');

        $this->assertInstanceOf(LocalSocket::class, $result);
    }

    public function test_local_socket_connect_does_not_throw(): void
    {
        $localServer = Server::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
            'is_local' => true,
        ]);

        $localSocket = new LocalSocket;
        $localSocket->init($localServer);

        // Connect should not throw since it's a no-op for local sockets
        $localSocket->connect();

        $this->assertTrue(true);
    }

    public function test_local_socket_disconnect_does_not_throw(): void
    {
        $localServer = Server::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
            'is_local' => true,
        ]);

        $localSocket = new LocalSocket;
        $localSocket->init($localServer);

        // Disconnect should not throw since there's no persistent connection
        $localSocket->disconnect();

        $this->assertTrue(true);
    }

    public function test_local_socket_fake_records_commands(): void
    {
        $localServer = Server::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
            'is_local' => true,
        ]);

        $fake = new LocalSocketFake('test output');
        $fake->init($localServer);

        $output = $fake->exec('echo "hello"');

        $this->assertEquals('test output', $output);
        $fake->assertExecutedContains('echo "hello"');
    }

    public function test_local_socket_fake_tracks_uploads(): void
    {
        $localServer = Server::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
            'is_local' => true,
        ]);

        $fake = new LocalSocketFake;
        $fake->init($localServer);

        // Create a temporary file for testing
        $tmpFile = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($tmpFile, 'test content');

        $fake->upload($tmpFile, '/remote/path/file.txt');

        $fake->assertFileUploaded('/remote/path/file.txt', 'test content');

        unlink($tmpFile);
    }

    public function test_default_server_is_not_local(): void
    {
        // The default server from TestCase should not be local
        $this->assertFalse($this->server->is_local);
    }

    public function test_facade_fake_works_for_local_servers(): void
    {
        $localServer = Server::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->user->current_project_id,
            'is_local' => true,
        ]);

        $fake = SSH::fake('mocked output');
        $connection = SSH::init($localServer);

        $output = $connection->exec('test command');

        $this->assertEquals('mocked output', $output);
        $fake->assertExecutedContains('test command');
    }
}
