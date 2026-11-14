<?php

use App\Events\SocketEvent;
use App\Facades\SSH;
use App\Models\ServerLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
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

test('log messages get their own lines while streamed output is kept exactly as received', function () {
    Storage::fake('server-logs');
    Event::fake([SocketEvent::class]);
    $log = ServerLog::factory()->create(['server_id' => $this->server->id, 'disk' => 'server-logs']);

    $log->write('Starting clone.');
    $log->writeOutput('Receiving objects: 50%');
    $log->writeOutput(", done.\nsecond line\n");
    $log->writeOutput('partial');
    $log->write('Starting build.');

    expect(Storage::disk('server-logs')->get($log->name))->toBe("Starting clone.\nReceiving objects: 50%, done.\nsecond line\npartial\nStarting build.\n");
    Event::assertDispatched(SocketEvent::class, fn (SocketEvent $event): bool => ($event->data->data['content'] ?? null) === 'Receiving objects: 50%');
    Event::assertDispatched(SocketEvent::class, fn (SocketEvent $event): bool => ($event->data->data['content'] ?? null) === "\nStarting build.\n");
});
