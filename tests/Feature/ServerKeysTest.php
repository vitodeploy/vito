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
        'public_key' => 'public-key-content',
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
        'public_key' => 'public-key-content',
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
        'public_key' => 'public-key-content',
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
        'public_key' => 'public-key-content',
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
        'public_key' => 'public-key-content',
    ]);

    $this->post(route('server-ssh-keys.store', ['server' => $this->server->id]), [
        'key' => $sshKey->id,
        'user' => 'invalid-user',
    ])
        ->assertSessionHasErrors(['user']);
});
