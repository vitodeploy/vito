<?php

namespace Tests\Feature;

use App\Enums\SshKeyStatus;
use App\Facades\SSH;
use App\Models\SshKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServerKeysTest extends TestCase
{
    use RefreshDatabase;

    public function test_see_server_keys()
    {
        $this->actingAs($this->user);

        $sshKey = SshKey::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'My first key',
            'public_key' => 'public-key-content',
        ]);

        $this->server->sshKeys()->attach($sshKey, [
            'status' => SshKeyStatus::ADDED,
        ]);

        $this->get(route('servers.ssh-keys', $this->server))
            ->assertSuccessful()
            ->assertSeeText('My first key');
    }

    public function test_delete_ssh_key()
    {
        SSH::fake();

        $this->actingAs($this->user);

        $sshKey = SshKey::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'My first key',
            'public_key' => 'public-key-content',
        ]);

        $this->server->sshKeys()->attach($sshKey, [
            'status' => SshKeyStatus::ADDED,
        ]);

        $this->delete(route('servers.ssh-keys.destroy', [$this->server, $sshKey]));

        $this->assertDatabaseMissing('server_ssh_keys', [
            'server_id' => $this->server->id,
            'ssh_key_id' => $sshKey->id,
        ]);
    }

    public function test_add_new_ssh_key()
    {
        SSH::fake();

        $this->actingAs($this->user);

        $this->post(route('servers.ssh-keys.store', $this->server), [
            'name' => 'My first key',
            'public_key' => 'ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABgQC3CCnyBbpCgOJ0AWUSfBZ+mYAsYzcQDegPkBx1kyE0bXT1yX4+6uYx1Jh6NxWgLyaU0BaP4nsClrK1u5FojQHd8J7ycc0N3H8B+v2NPzj1Q6bFnl40saastONVm+d4edbCg9BowGAafLcf9ALsognqqOWQbK/QOpAhg25IAe47eiY3IjDGMHlsvaZkMtkDhT4t1mK8ZLjxw5vjyVYgINJefR981bIxMFrXy+0xBCsYOZxMIoAJsgCkrAGlI4kQHKv0SQVccSyTE1eziIZa5b3QUlXj8ogxMfK/EOD7Aoqinw652k4S5CwFs/LLmjWcFqCKDM6CSggWpB78DZ729O6zFvQS9V99/9SsSV7Qc5ML7B0DKzJ/tbHkaAE8xdZnQnZFVUegUMtUmjvngMaGlYsxkAZrUKsFRoh7xfXVkDyRBaBSslRNe8LFsXw9f7Q+3jdZ5vhGhmp+TBXTlgxApwR023411+ABE9y0doCx8illya3m2olEiiMZkRclgqsWFSk=',
        ]);

        $this->assertDatabaseHas('server_ssh_keys', [
            'server_id' => $this->server->id,
            'status' => SshKeyStatus::ADDED,
        ]);
    }

    public function test_add_existing_key()
    {
        SSH::fake();

        $this->actingAs($this->user);

        $sshKey = SshKey::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'My first key',
            'public_key' => 'public-key-content',
        ]);

        $this->post(route('servers.ssh-keys.deploy', $this->server), [
            'key_id' => $sshKey->id,
        ]);

        $this->assertDatabaseHas('server_ssh_keys', [
            'server_id' => $this->server->id,
            'status' => SshKeyStatus::ADDED,
        ]);
    }
}
