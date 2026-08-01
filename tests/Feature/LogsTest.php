<?php

use App\Facades\SSH;
use App\Models\ServerLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

test('see logs', function () {
    $this->actingAs($this->user);

    ServerLog::factory()->create([
        'server_id' => $this->server->id,
    ]);

    $this->get(route('logs', $this->server))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('server-logs/index'));
});

test('see logs remote', function () {
    $this->actingAs($this->user);

    ServerLog::factory()->create([
        'server_id' => $this->server->id,
        'is_remote' => true,
        'type' => 'remote',
        'name' => 'see-remote-log',
    ]);

    $this->get(route('logs.remote', $this->server))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('server-logs/index'));
});

test('create remote log', function () {
    $this->actingAs($this->user);

    $this->post(route('logs.store', $this->server), [
        'path' => 'test-path',
    ])
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('server_logs', [
        'is_remote' => true,
        'name' => 'test-path',
    ]);
});

test('clear remote log', function () {
    $this->actingAs($this->user);

    $log = ServerLog::factory()->create([
        'server_id' => $this->server->id,
        'is_remote' => true,
        'type' => 'remote',
        'name' => 'test-remote-log',
    ]);

    // Mock the SSH connection to avoid actual SSH calls
    SSH::fake();

    $this->post(route('logs.clear', [$this->server, 'log' => $log->id]))
        ->assertRedirect()
        ->assertSessionHas('success', 'Log cleared successfully');
});

test('unauthorized user cannot clear log', function () {
    /** @var User $unauthorizedUser */
    $unauthorizedUser = User::factory()->create();
    $this->actingAs($unauthorizedUser);

    $log = ServerLog::factory()->create([
        'server_id' => $this->server->id,
        'is_remote' => true,
        'type' => 'remote',
        'name' => 'test-remote-log',
    ]);

    $this->post(route('logs.clear', [$this->server, 'log' => $log->id]))
        ->assertForbidden();
});
