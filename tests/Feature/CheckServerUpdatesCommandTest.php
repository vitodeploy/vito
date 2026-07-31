<?php

use App\Enums\ServerStatus;
use App\Jobs\Server\CheckForUpdatesJob;
use App\Models\Server;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;

uses(RefreshDatabase::class);

test('dispatches job for ready servers', function () {
    Bus::fake();

    $this->artisan('servers:check-updates')->assertSuccessful();

    Bus::assertDispatched(CheckForUpdatesJob::class, function (CheckForUpdatesJob $job) {
        return $job->queue === 'ssh';
    });
});

test('does not dispatch for disconnected servers', function () {
    Bus::fake();

    $this->server->update(['status' => ServerStatus::DISCONNECTED]);

    $this->artisan('servers:check-updates')->assertSuccessful();

    Bus::assertNotDispatched(CheckForUpdatesJob::class);
});

test('dispatches for each ready server', function () {
    Bus::fake();

    Server::factory()->count(2)->create([
        'user_id' => $this->user->id,
        'project_id' => $this->user->current_project_id,
        'status' => ServerStatus::READY,
    ]);

    Server::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->user->current_project_id,
        'status' => ServerStatus::DISCONNECTED,
    ]);

    $this->artisan('servers:check-updates')->assertSuccessful();

    Bus::assertDispatchedTimes(CheckForUpdatesJob::class, 3);
});
