<?php

use App\Enums\CronjobStatus;
use App\Facades\SSH;
use App\Models\CronJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('see cronjobs list', function () {
    Sanctum::actingAs($this->user, ['read', 'write']);

    /** @var CronJob $cronjob */
    $cronjob = CronJob::factory()->create([
        'server_id' => $this->server->id,
    ]);

    $this->json('GET', route('api.projects.servers.cron-jobs', [
        'project' => $this->server->project,
        'server' => $this->server,
    ]))
        ->assertSuccessful()
        ->assertJsonFragment([
            'command' => $cronjob->command,
            'frequency' => $cronjob->frequency,
        ]);
});

test('create cronjob', function () {
    SSH::fake();

    Sanctum::actingAs($this->user, ['read', 'write']);

    $this->json('POST', route('api.projects.servers.cron-jobs.create', [
        'project' => $this->server->project,
        'server' => $this->server,
    ]), [
        'command' => 'ls -la',
        'user' => 'vito',
        'frequency' => '* * * * *',
    ])
        ->assertSuccessful()
        ->assertJsonFragment([
            'command' => 'ls -la',
            'user' => 'vito',
            'frequency' => '* * * * *',
            'status' => CronjobStatus::READY,
        ]);
});

test('delete cronjob', function () {
    SSH::fake();

    Sanctum::actingAs($this->user, ['read', 'write']);

    /** @var CronJob $cronjob */
    $cronjob = CronJob::factory()->create([
        'server_id' => $this->server->id,
        'user' => 'vito',
    ]);

    $this->json('DELETE', route('api.projects.servers.cron-jobs.delete', [
        'project' => $this->server->project,
        'server' => $this->server,
        'cronJob' => $cronjob,
    ]))
        ->assertSuccessful()
        ->assertNoContent();
});

test('cannot create cronjob with invalid site id api', function () {
    SSH::fake();
    Sanctum::actingAs($this->user, ['read', 'write']);

    $this->json('POST', route('api.projects.servers.cron-jobs.create', [
        'project' => $this->server->project,
        'server' => $this->server,
    ]), [
        'command' => 'ls -la',
        'user' => 'vito',
        'frequency' => '* * * * *',
        'site_id' => 99999, // Non-existent site ID
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['site_id']);

    $this->assertDatabaseMissing('cron_jobs', [
        'server_id' => $this->server->id,
        'site_id' => 99999,
    ]);
});
