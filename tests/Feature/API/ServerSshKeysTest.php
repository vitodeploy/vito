<?php

namespace Tests\Feature\API;

use App\Enums\SshKeyStatus;
use App\Facades\SSH;
use App\Models\SshKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ServerSshKeysTest extends TestCase
{
    use RefreshDatabase;

    public function test_see_server_keys()
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        $sshKey = SshKey::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'My first key',
            'public_key' => 'public-key-content',
        ]);

        $this->server->sshKeys()->attach($sshKey, [
            'status' => SshKeyStatus::ADDED,
        ]);

        $this->json('GET', route('api.projects.servers.ssh-keys', [
            'project' => $this->server->project,
            'server' => $this->server,
        ]))
            ->assertSuccessful()
            ->assertJsonFragment([
                'name' => 'My first key',
            ]);
    }

    public function test_add_new_ssh_key()
    {
        SSH::fake();

        Sanctum::actingAs($this->user, ['read', 'write']);

        $this->json('POST', route('api.projects.servers.ssh-keys.create', [
            'project' => $this->server->project,
            'server' => $this->server,
        ]), [
            'name' => 'My first key',
            'public_key' => 'ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABgQC3CCnyBbpCgOJ0AWUSfBZ+mYAsYzcQDegPkBx1kyE0bXT1yX4+6uYx1Jh6NxWgLyaU0BaP4nsClrK1u5FojQHd8J7ycc0N3H8B+v2NPzj1Q6bFnl40saastONVm+d4edbCg9BowGAafLcf9ALsognqqOWQbK/QOpAhg25IAe47eiY3IjDGMHlsvaZkMtkDhT4t1mK8ZLjxw5vjyVYgINJefR981bIxMFrXy+0xBCsYOZxMIoAJsgCkrAGlI4kQHKv0SQVccSyTE1eziIZa5b3QUlXj8ogxMfK/EOD7Aoqinw652k4S5CwFs/LLmjWcFqCKDM6CSggWpB78DZ729O6zFvQS9V99/9SsSV7Qc5ML7B0DKzJ/tbHkaAE8xdZnQnZFVUegUMtUmjvngMaGlYsxkAZrUKsFRoh7xfXVkDyRBaBSslRNe8LFsXw9f7Q+3jdZ5vhGhmp+TBXTlgxApwR023411+ABE9y0doCx8illya3m2olEiiMZkRclgqsWFSk=',
        ])
            ->assertSuccessful()
            ->assertJsonFragment([
                'name' => 'My first key',
            ]);
    }

    public function test_add_existing_key()
    {
        SSH::fake();

        Sanctum::actingAs($this->user, ['read', 'write']);

        /** @var SshKey $sshKey */
        $sshKey = SshKey::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'My first key',
            'public_key' => 'public-key-content',
        ]);

        $this->json('POST', route('api.projects.servers.ssh-keys.create', [
            'project' => $this->server->project,
            'server' => $this->server,
        ]), [
            'key_id' => $sshKey->id,
        ])
            ->assertSuccessful()
            ->assertJsonFragment([
                'name' => 'My first key',
            ]);
    }

    public function test_delete_ssh_key()
    {
        SSH::fake();

        Sanctum::actingAs($this->user, ['read', 'write']);

        /** @var SshKey $sshKey */
        $sshKey = SshKey::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'My first key',
            'public_key' => 'public-key-content',
        ]);

        $this->server->sshKeys()->attach($sshKey, [
            'status' => SshKeyStatus::ADDED,
        ]);

        $this->json('DELETE', route('api.projects.servers.ssh-keys.delete', [
            'project' => $this->server->project,
            'server' => $this->server,
            'sshKey' => $sshKey,
        ]))
            ->assertSuccessful()
            ->assertNoContent();
    }
}
