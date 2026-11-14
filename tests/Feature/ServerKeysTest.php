<?php

use App\Enums\SshKeyStatus;
use App\Facades\SSH;
use App\Models\SshKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

test('see server keys', function () {
    $this->actingAs($this->user);

    $sshKey = SshKey::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'My first key',
        'public_key' => 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIDrGW6u1UNcrPWNloh/cTUAhAQcxtPHquUT4d7MkGfwI vito-test',
    ]);

    $this->server->sshKeys()->attach($sshKey, [
        'status' => SshKeyStatus::ADDED,
        'user' => $this->server->getSshUser(),
    ]);

    $this->get(route('server-ssh-keys', ['server' => $this->server->id]))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('server-ssh-keys/index'));
});

test('delete ssh key', function () {
    SSH::fake();

    $this->actingAs($this->user);

    $sshKey = SshKey::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'My first key',
        'public_key' => 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIDrGW6u1UNcrPWNloh/cTUAhAQcxtPHquUT4d7MkGfwI vito-test',
    ]);

    $this->server->sshKeys()->attach($sshKey, [
        'status' => SshKeyStatus::ADDED,
        'user' => $this->server->getSshUser(),
    ]);

    $this->delete(route('server-ssh-keys.destroy', ['server' => $this->server->id, 'sshKey' => $sshKey->id]))
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseMissing('server_ssh_keys', [
        'server_id' => $this->server->id,
        'ssh_key_id' => $sshKey->id,
    ]);
});

test('add existing key', function () {
    SSH::fake();

    $this->actingAs($this->user);

    $sshKey = SshKey::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'My first key',
        'public_key' => 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIDrGW6u1UNcrPWNloh/cTUAhAQcxtPHquUT4d7MkGfwI vito-test',
    ]);

    $this->post(route('server-ssh-keys.store', ['server' => $this->server->id]), [
        'key' => $sshKey->id,
        'user' => $this->server->getSshUser(),
    ])
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('server_ssh_keys', [
        'server_id' => $this->server->id,
        'status' => SshKeyStatus::ADDED,
        'user' => $this->server->getSshUser(),
    ]);
});

test('add key to specific user', function () {
    SSH::fake();

    $this->actingAs($this->user);

    $sshKey = SshKey::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'My first key',
        'public_key' => 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIDrGW6u1UNcrPWNloh/cTUAhAQcxtPHquUT4d7MkGfwI vito-test',
    ]);

    $targetUser = 'root';

    $this->post(route('server-ssh-keys.store', ['server' => $this->server->id]), [
        'key' => $sshKey->id,
        'user' => $targetUser,
    ])
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('server_ssh_keys', [
        'server_id' => $this->server->id,
        'status' => SshKeyStatus::ADDED,
        'user' => $targetUser,
    ]);
});

test('add key to invalid user fails', function () {
    SSH::fake();

    $this->actingAs($this->user);

    $sshKey = SshKey::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'My first key',
        'public_key' => 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIDrGW6u1UNcrPWNloh/cTUAhAQcxtPHquUT4d7MkGfwI vito-test',
    ]);

    $this->post(route('server-ssh-keys.store', ['server' => $this->server->id]), [
        'key' => $sshKey->id,
        'user' => 'invalid-user',
    ])
        ->assertSessionHasErrors(['user']);
});

test('deploying a key with a hostile comment keeps it a single shell argument', function () {
    SSH::fake();

    $this->actingAs($this->user);

    $sshKey = SshKey::factory()->create([
        'user_id' => $this->user->id,
        'public_key' => "ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIDrGW6u1UNcrPWNloh/cTUAhAQcxtPHquUT4d7MkGfwI x'; touch /tmp/pwned; echo '",
    ]);

    $this->post(route('server-ssh-keys.store', ['server' => $this->server->id]), [
        'key' => $sshKey->id,
        'user' => $this->server->getSshUser(),
    ])->assertSessionDoesntHaveErrors();

    SSH::assertExecutedContains("printf '%s\\n' 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIDrGW6u1UNcrPWNloh/cTUAhAQcxtPHquUT4d7MkGfwI x'\\''; touch /tmp/pwned; echo '\\''' >> ~/.ssh/authorized_keys");
});

test('deleting a key matches its key material and ignores the comment', function () {
    SSH::fake();

    $this->actingAs($this->user);

    $sshKey = SshKey::factory()->create([
        'user_id' => $this->user->id,
        'public_key' => 'ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIDrGW6u1UNcrPWNloh/cTUAhAQcxtPHquUT4d7MkGfwI "quoted" comment',
    ]);

    $this->server->sshKeys()->attach($sshKey, [
        'status' => SshKeyStatus::ADDED,
        'user' => $this->server->getSshUser(),
    ]);

    $this->delete(route('server-ssh-keys.destroy', ['server' => $this->server->id, 'sshKey' => $sshKey->id]))
        ->assertSessionDoesntHaveErrors();

    SSH::assertExecutedContains("awk -v key='AAAAC3NzaC1lZDI1NTE5AAAAIDrGW6u1UNcrPWNloh/cTUAhAQcxtPHquUT4d7MkGfwI'");
    SSH::assertNotExecutedContains('quoted');
});

test('a key without valid key material is not detached from the server', function () {
    SSH::fake();

    $this->actingAs($this->user);

    $sshKey = SshKey::factory()->create([
        'user_id' => $this->user->id,
        'public_key' => "ssh-ed25519 not-base64';id;'",
    ]);

    $this->server->sshKeys()->attach($sshKey, [
        'status' => SshKeyStatus::ADDED,
        'user' => $this->server->getSshUser(),
    ]);

    $this->delete(route('server-ssh-keys.destroy', ['server' => $this->server->id, 'sshKey' => $sshKey->id]));

    $this->assertDatabaseHas('server_ssh_keys', [
        'server_id' => $this->server->id,
        'ssh_key_id' => $sshKey->id,
    ]);
});
