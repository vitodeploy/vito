<?php

use App\Enums\ServerStatus;
use App\Jobs\Server\InstallJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

test('install job failed sets status to installation failed and logs', function () {
    Notification::fake();

    $this->server->update(['status' => ServerStatus::INSTALLING]);

    $job = new InstallJob($this->server);
    $job->failed(new Exception('Installation failed'));

    $this->server->refresh();

    expect($this->server->status)->toEqual(ServerStatus::INSTALLATION_FAILED);

    $this->assertDatabaseHas('server_logs', [
        'server_id' => $this->server->id,
        'type' => 'server-installation-failed',
    ]);
});
