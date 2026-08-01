<?php

use App\Enums\CronjobStatus;
use App\Facades\SSH;
use App\Models\CronJob;
use App\Models\Server;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('see site cronjobs list', function () {
    Sanctum::actingAs($this->user, ['read']);

    /** @var Site $site */
    $site = Site::factory()->create([
        'server_id' => $this->server->id,
    ]);

    $this->json('GET', route('api.projects.servers.sites.cron-jobs', [
        'project' => $this->server->project,
        'server' => $this->server,
        'site' => $site,
    ]))
        ->assertSuccessful();
});

test('see site cronjob', function () {
    Sanctum::actingAs($this->user, ['read']);

    /** @var Site $site */
    $site = Site::factory()->create([
        'server_id' => $this->server->id,
    ]);

    /** @var CronJob $cronjob */
    $cronjob = CronJob::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $site->id,
    ]);

    $this->json('GET', route('api.projects.servers.sites.cron-jobs.show', [
        'project' => $this->server->project,
        'server' => $this->server,
        'site' => $site,
        'cronJob' => $cronjob,
    ]))
        ->assertSuccessful();
});

test('create site cronjob', function () {
    SSH::fake();

    Sanctum::actingAs($this->user, ['read', 'write']);

    /** @var Site $site */
    $site = Site::factory()->create([
        'server_id' => $this->server->id,
    ]);

    $this->json('POST', route('api.projects.servers.sites.cron-jobs.create', [
        'project' => $this->server->project,
        'server' => $this->server,
        'site' => $site,
    ]), [
        'command' => 'ls -la',
        'user' => 'vito',
        'frequency' => '* * * * *',
        'name' => 'My Site Cronjob',
    ])
        ->assertSuccessful()
        ->assertJsonFragment([
            'command' => 'ls -la',
            'user' => 'vito',
            'frequency' => '* * * * *',
            'status' => CronjobStatus::READY,
            'site_id' => $site->id,
        ]);

    $this->assertDatabaseHas('cron_jobs', [
        'server_id' => $this->server->id,
        'site_id' => $site->id,
        'command' => 'ls -la',
        'user' => 'vito',
        'frequency' => '* * * * *',
        'status' => CronjobStatus::READY,
        'name' => 'My Site Cronjob',
    ]);
});

test('create site cronjob for isolated user', function () {
    SSH::fake();

    Sanctum::actingAs($this->user, ['read', 'write']);

    /** @var Site $site */
    $site = Site::factory()->create([
        'server_id' => $this->server->id,
        'user' => 'example',
    ]);

    $this->json('POST', route('api.projects.servers.sites.cron-jobs.create', [
        'project' => $this->server->project,
        'server' => $this->server,
        'site' => $site,
    ]), [
        'command' => 'ls -la',
        'user' => 'example',
        'frequency' => '* * * * *',
    ])
        ->assertSuccessful()
        ->assertJsonFragment([
            'command' => 'ls -la',
            'user' => 'example',
            'frequency' => '* * * * *',
            'status' => CronjobStatus::READY,
            'site_id' => $site->id,
        ]);

    $this->assertDatabaseHas('cron_jobs', [
        'server_id' => $this->server->id,
        'site_id' => $site->id,
        'user' => 'example',
    ]);
});

test('cannot create site cronjob for non existing user', function () {
    SSH::fake();

    Sanctum::actingAs($this->user, ['read', 'write']);

    /** @var Site $site */
    $site = Site::factory()->create([
        'server_id' => $this->server->id,
    ]);

    $this->json('POST', route('api.projects.servers.sites.cron-jobs.create', [
        'project' => $this->server->project,
        'server' => $this->server,
        'site' => $site,
    ]), [
        'command' => 'ls -la',
        'user' => 'nonexistent',
        'frequency' => '* * * * *',
    ])
        ->assertStatus(422);

    $this->assertDatabaseMissing('cron_jobs', [
        'server_id' => $this->server->id,
        'site_id' => $site->id,
        'user' => 'nonexistent',
    ]);
});

test('delete site cronjob', function () {
    SSH::fake();

    Sanctum::actingAs($this->user, ['read', 'write']);

    /** @var Site $site */
    $site = Site::factory()->create([
        'server_id' => $this->server->id,
    ]);

    /** @var CronJob $cronjob */
    $cronjob = CronJob::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $site->id,
    ]);

    $this->json('DELETE', route('api.projects.servers.sites.cron-jobs.delete', [
        'project' => $this->server->project,
        'server' => $this->server,
        'site' => $site,
        'cronJob' => $cronjob,
    ]))
        ->assertStatus(204);

    $this->assertDatabaseMissing('cron_jobs', [
        'id' => $cronjob->id,
    ]);
});

test('cannot access cronjob from different site', function () {
    Sanctum::actingAs($this->user, ['read']);

    /** @var Site $site1 */
    $site1 = Site::factory()->create([
        'server_id' => $this->server->id,
    ]);

    /** @var Site $site2 */
    $site2 = Site::factory()->create([
        'server_id' => $this->server->id,
    ]);

    /** @var CronJob $cronjob */
    $cronjob = CronJob::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $site1->id,
    ]);

    $this->json('GET', route('api.projects.servers.sites.cron-jobs.show', [
        'project' => $this->server->project,
        'server' => $this->server,
        'site' => $site2,
        'cronJob' => $cronjob,
    ]))
        ->assertStatus(404);
});

test('cannot access cronjob from different server', function () {
    Sanctum::actingAs($this->user, ['read']);

    /** @var Site $site */
    $site = Site::factory()->create([
        'server_id' => $this->server->id,
    ]);

    /** @var CronJob $cronjob */
    $cronjob = CronJob::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $site->id,
    ]);

    // Create another server and try to access the cronjob through it
    $otherServer = Server::factory()->create([
        'project_id' => $this->server->project->id,
    ]);

    $this->json('GET', route('api.projects.servers.sites.cron-jobs.show', [
        'project' => $this->server->project,
        'server' => $otherServer,
        'site' => $site,
        'cronJob' => $cronjob,
    ]))
        ->assertStatus(403);
});
