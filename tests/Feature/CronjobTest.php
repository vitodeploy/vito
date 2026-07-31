<?php

use App\Actions\CronJob\SyncCronJobs;
use App\Enums\CronjobStatus;
use App\Facades\SSH;
use App\Models\CronJob;
use App\Models\Server;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

test('see cronjobs list', function () {
    $this->actingAs($this->user);

    CronJob::factory()->create([
        'server_id' => $this->server->id,
    ]);

    $this->get(route('cronjobs', $this->server))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('cronjobs/index'));
});

test('delete cronjob', function () {
    SSH::fake();

    $this->actingAs($this->user);

    /** @var CronJob $cronjob */
    $cronjob = CronJob::factory()->create([
        'server_id' => $this->server->id,
        'user' => 'vito',
    ]);

    $this->delete(route('cronjobs.destroy', [
        'server' => $this->server,
        'cronJob' => $cronjob,
    ]));

    $this->assertDatabaseMissing('cron_jobs', [
        'id' => $cronjob->id,
    ]);

    SSH::assertExecutedContains("echo '' | sudo -u vito crontab -");
    SSH::assertExecutedContains('sudo -u vito crontab -l');
});

test('create cronjob', function () {
    SSH::fake();

    $this->actingAs($this->user);

    $this->post(route('cronjobs.store', ['server' => $this->server]), [
        'command' => 'ls -la',
        'user' => 'vito',
        'frequency' => '* * * * *',
        'name' => 'My Cronjob',
    ])
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('cron_jobs', [
        'server_id' => $this->server->id,
        'command' => 'ls -la',
        'user' => 'vito',
        'frequency' => '* * * * *',
        'status' => CronjobStatus::READY,
        'name' => 'My Cronjob',
    ]);

    SSH::assertExecutedContains("echo '* * * * * bash -lc '\\''ls -la'\\''' | sudo -u vito crontab -");
    SSH::assertExecutedContains('sudo -u vito crontab -l');
});

test('create cronjob for isolated user', function () {
    SSH::fake();
    $this->actingAs($this->user);

    $this->site->user = 'example';
    $this->site->save();

    $this->post(route('cronjobs.store', ['server' => $this->server]), [
        'command' => 'ls -la',
        'user' => 'example',
        'frequency' => '* * * * *',
    ])
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('cron_jobs', [
        'server_id' => $this->server->id,
        'user' => 'example',
    ]);

    SSH::assertExecutedContains("echo '* * * * * bash -lc '\\''ls -la'\\''' | sudo -u example crontab -");
    SSH::assertExecutedContains('sudo -u example crontab -l');
});

test('cannot create cronjob for non existing user', function () {
    SSH::fake();
    $this->actingAs($this->user);

    $this->post(route('cronjobs.store', ['server' => $this->server]), [
        'command' => 'ls -la',
        'user' => 'example',
        'frequency' => '* * * * *',
    ])
        ->assertSessionHasErrors();

    $this->assertDatabaseMissing('cron_jobs', [
        'server_id' => $this->server->id,
        'user' => 'example',
    ]);
});

test('cannot create cronjob for user on another server', function () {
    SSH::fake();
    $this->actingAs($this->user);

    Site::factory()->create([
        'server_id' => Server::factory()->create(['user_id' => 1])->id,
        'user' => 'example',
    ]);

    $this->post(route('cronjobs.store', ['server' => $this->server]), [
        'command' => 'ls -la',
        'user' => 'example',
        'frequency' => '* * * * *',
    ])
        ->assertSessionHasErrors();

    $this->assertDatabaseMissing('cron_jobs', [
        'user' => 'example',
    ]);
});

test('create custom cronjob', function () {
    SSH::fake();

    $this->actingAs($this->user);

    $this->post(route('cronjobs.store', ['server' => $this->server]), [
        'command' => 'ls -la',
        'user' => 'vito',
        'frequency' => 'custom',
        'custom' => '* * * 1 1',
    ])
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('cron_jobs', [
        'server_id' => $this->server->id,
        'command' => 'ls -la',
        'user' => 'vito',
        'frequency' => '* * * 1 1',
        'status' => CronjobStatus::READY,
    ]);

    SSH::assertExecutedContains("echo '* * * 1 1 bash -lc '\\''ls -la'\\''' | sudo -u vito crontab -");
    SSH::assertExecutedContains('sudo -u vito crontab -l');
});

test('cronjob is wrapped in login shell and survives sync', function () {
    SSH::fake();
    $this->actingAs($this->user);

    /** @var CronJob $cronjob */
    $cronjob = CronJob::factory()->create([
        'server_id' => $this->server->id,
        'user' => 'vito',
        'command' => "echo 'hi'",
        'frequency' => '* * * * *',
        'status' => CronjobStatus::READY,
        'site_id' => null,
    ]);

    $crontab = CronJob::crontab($this->server, 'vito');
    expect($crontab)->toBe("* * * * * bash -lc 'echo '\\''hi'\\'''");
    expect(CronJob::unwrapCommand("bash -lc 'echo '\\''hi'\\'''"))->toBe("echo 'hi'");

    SSH::fake($crontab);
    app(SyncCronJobs::class)->sync($this->server);

    $cronjob->refresh();
    expect($cronjob->status)->toBe(CronjobStatus::READY);
    expect(CronJob::query()->where('user', 'vito')->where('command', "echo 'hi'")->count())->toBe(1);
});

test('root cronjob is not wrapped', function () {
    $this->actingAs($this->user);

    CronJob::factory()->create([
        'server_id' => $this->server->id,
        'user' => 'root',
        'command' => '/usr/bin/backup.sh',
        'frequency' => '0 2 * * *',
        'status' => CronjobStatus::READY,
        'site_id' => null,
    ]);

    expect(CronJob::crontab($this->server, 'root'))->toBe('0 2 * * * /usr/bin/backup.sh');
});

test('enable cronjob', function () {
    SSH::fake();

    $this->actingAs($this->user);

    /** @var CronJob $cronjob */
    $cronjob = CronJob::factory()->create([
        'server_id' => $this->server->id,
        'user' => 'vito',
        'command' => 'ls -la',
        'frequency' => '* * * 1 1',
        'status' => CronjobStatus::DISABLED,
    ]);

    $this->post(route('cronjobs.enable', [
        'server' => $this->server,
        'cronJob' => $cronjob,
    ]))
        ->assertSessionDoesntHaveErrors();

    $cronjob->refresh();

    expect($cronjob->status)->toEqual(CronjobStatus::READY);

    SSH::assertExecutedContains("echo '* * * 1 1 bash -lc '\\''ls -la'\\''' | sudo -u vito crontab -");
    SSH::assertExecutedContains('sudo -u vito crontab -l');
});

test('disable cronjob', function () {
    SSH::fake();

    $this->actingAs($this->user);

    /** @var CronJob $cronjob */
    $cronjob = CronJob::factory()->create([
        'server_id' => $this->server->id,
        'user' => 'vito',
        'command' => 'ls -la',
        'frequency' => '* * * 1 1',
        'status' => CronjobStatus::READY,
    ]);

    $this->post(route('cronjobs.disable', [
        'server' => $this->server,
        'cronJob' => $cronjob,
    ]))
        ->assertSessionDoesntHaveErrors();

    $cronjob->refresh();

    expect($cronjob->status)->toEqual(CronjobStatus::DISABLED);

    SSH::assertExecutedContains("echo '' | sudo -u vito crontab -");
    SSH::assertExecutedContains('sudo -u vito crontab -l');
});

test('create cronjob with valid site id', function () {
    SSH::fake();

    $this->actingAs($this->user);

    /** @var Site $site */
    $site = Site::factory()->create([
        'server_id' => $this->server->id,
    ]);

    $this->post(route('cronjobs.store', ['server' => $this->server]), [
        'command' => 'ls -la',
        'user' => 'vito',
        'frequency' => '* * * * *',
        'site_id' => $site->id,
    ])
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('cron_jobs', [
        'server_id' => $this->server->id,
        'site_id' => $site->id,
        'command' => 'ls -la',
        'user' => 'vito',
        'frequency' => '* * * * *',
        'status' => CronjobStatus::READY,
    ]);
});

test('cannot create cronjob with invalid site id', function () {
    SSH::fake();
    $this->actingAs($this->user);

    $this->post(route('cronjobs.store', ['server' => $this->server]), [
        'command' => 'ls -la',
        'user' => 'vito',
        'frequency' => '* * * * *',
        'site_id' => 99999, // Non-existent site ID
    ])
        ->assertSessionHasErrors(['site_id']);

    $this->assertDatabaseMissing('cron_jobs', [
        'server_id' => $this->server->id,
        'site_id' => 99999,
    ]);
});

test('cannot create cronjob with site id from different server', function () {
    SSH::fake();
    $this->actingAs($this->user);

    /** @var Server $otherServer */
    $otherServer = Server::factory()->create(['user_id' => 1]);

    /** @var Site $otherSite */
    $otherSite = Site::factory()->create([
        'server_id' => $otherServer->id,
    ]);

    $this->post(route('cronjobs.store', ['server' => $this->server]), [
        'command' => 'ls -la',
        'user' => 'vito',
        'frequency' => '* * * * *',
        'site_id' => $otherSite->id,
    ])
        ->assertSessionHasErrors(['site_id']);

    $this->assertDatabaseMissing('cron_jobs', [
        'server_id' => $this->server->id,
        'site_id' => $otherSite->id,
    ]);
});

test('edit cronjob with valid site id', function () {
    SSH::fake();

    $this->actingAs($this->user);

    /** @var CronJob $cronjob */
    $cronjob = CronJob::factory()->create([
        'server_id' => $this->server->id,
    ]);

    /** @var Site $site */
    $site = Site::factory()->create([
        'server_id' => $this->server->id,
    ]);

    $this->put(route('cronjobs.update', [
        'server' => $this->server,
        'cronJob' => $cronjob,
    ]), [
        'command' => 'updated command',
        'user' => 'vito',
        'frequency' => '* * * * *',
        'site_id' => $site->id,
        'name' => 'Updated Cronjob',
    ])
        ->assertSessionDoesntHaveErrors();

    $cronjob->refresh();

    expect($cronjob->site_id)->toEqual($site->id);
    expect($cronjob->command)->toEqual('updated command');
    expect($cronjob->name)->toEqual('Updated Cronjob');
});

test('cannot edit cronjob with invalid site id', function () {
    SSH::fake();
    $this->actingAs($this->user);

    /** @var CronJob $cronjob */
    $cronjob = CronJob::factory()->create([
        'server_id' => $this->server->id,
    ]);

    $this->put(route('cronjobs.update', [
        'server' => $this->server,
        'cronJob' => $cronjob,
    ]), [
        'command' => 'updated command',
        'user' => 'vito',
        'frequency' => '* * * * *',
        'site_id' => 99999, // Non-existent site ID
    ])
        ->assertSessionHasErrors(['site_id']);

    $cronjob->refresh();

    $this->assertNotEquals(99999, $cronjob->site_id);
});

test('cannot edit cronjob with site id from different server', function () {
    SSH::fake();
    $this->actingAs($this->user);

    /** @var CronJob $cronjob */
    $cronjob = CronJob::factory()->create([
        'server_id' => $this->server->id,
    ]);

    /** @var Server $otherServer */
    $otherServer = Server::factory()->create(['user_id' => 1]);

    /** @var Site $otherSite */
    $otherSite = Site::factory()->create([
        'server_id' => $otherServer->id,
    ]);

    $this->put(route('cronjobs.update', [
        'server' => $this->server,
        'cronJob' => $cronjob,
    ]), [
        'command' => 'updated command',
        'user' => 'vito',
        'frequency' => '* * * * *',
        'site_id' => $otherSite->id,
    ])
        ->assertSessionHasErrors(['site_id']);

    $cronjob->refresh();

    $this->assertNotEquals($otherSite->id, $cronjob->site_id);
});
