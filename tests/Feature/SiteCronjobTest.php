<?php

use App\Enums\CronjobStatus;
use App\Facades\SSH;
use App\Models\CronJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

test('see site cronjobs list', function () {
    $this->actingAs($this->user);

    CronJob::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $this->site->id,
    ]);

    $this->get(route('cronjobs.site', [
        'server' => $this->server,
        'site' => $this->site,
    ]))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('cronjobs/index'));
});

test('delete site cronjob', function () {
    SSH::fake();

    $this->actingAs($this->user);

    /** @var CronJob $cronjob */
    $cronjob = CronJob::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $this->site->id,
        'user' => 'vito',
    ]);

    $this->delete(route('cronjobs.site.destroy', [
        'server' => $this->server,
        'site' => $this->site,
        'cronJob' => $cronjob,
    ]));

    $this->assertDatabaseMissing('cron_jobs', [
        'id' => $cronjob->id,
    ]);

    SSH::assertExecutedContains("echo '' | sudo -u vito crontab -");
    SSH::assertExecutedContains('sudo -u vito crontab -l');
});

test('create site cronjob', function () {
    SSH::fake();

    $this->actingAs($this->user);

    $this->post(route('cronjobs.site.store', [
        'server' => $this->server,
        'site' => $this->site,
    ]), [
        'command' => 'ls -la',
        'user' => 'vito',
        'frequency' => '* * * * *',
    ])
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('cron_jobs', [
        'server_id' => $this->server->id,
        'site_id' => $this->site->id,
        'command' => 'ls -la',
        'user' => 'vito',
        'frequency' => '* * * * *',
        'status' => CronjobStatus::READY,
    ]);

    SSH::assertExecutedContains("echo '* * * * * bash -lc '\\''ls -la'\\''' | sudo -u vito crontab -");
    SSH::assertExecutedContains('sudo -u vito crontab -l');
});

test('create site cronjob for isolated user', function () {
    SSH::fake();
    $this->actingAs($this->user);

    $this->site->user = 'example';
    $this->site->save();

    $this->post(route('cronjobs.site.store', [
        'server' => $this->server,
        'site' => $this->site,
    ]), [
        'command' => 'ls -la',
        'user' => 'example',
        'frequency' => '* * * * *',
    ])
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('cron_jobs', [
        'server_id' => $this->server->id,
        'site_id' => $this->site->id,
        'user' => 'example',
    ]);

    SSH::assertExecutedContains("echo '* * * * * bash -lc '\\''ls -la'\\''' | sudo -u example crontab -");
    SSH::assertExecutedContains('sudo -u example crontab -l');
});

test('cannot create site cronjob for non existing user', function () {
    SSH::fake();
    $this->actingAs($this->user);

    $this->post(route('cronjobs.site.store', [
        'server' => $this->server,
        'site' => $this->site,
    ]), [
        'command' => 'ls -la',
        'user' => 'nonexistent',
        'frequency' => '* * * * *',
    ])
        ->assertSessionHasErrors();

    $this->assertDatabaseMissing('cron_jobs', [
        'server_id' => $this->server->id,
        'site_id' => $this->site->id,
        'user' => 'nonexistent',
    ]);
});

test('create custom site cronjob', function () {
    SSH::fake();

    $this->actingAs($this->user);

    $this->post(route('cronjobs.site.store', [
        'server' => $this->server,
        'site' => $this->site,
    ]), [
        'command' => 'ls -la',
        'user' => 'vito',
        'frequency' => 'custom',
        'custom' => '* * * 1 1',
    ])
        ->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('cron_jobs', [
        'server_id' => $this->server->id,
        'site_id' => $this->site->id,
        'command' => 'ls -la',
        'user' => 'vito',
        'frequency' => '* * * 1 1',
        'status' => CronjobStatus::READY,
    ]);

    SSH::assertExecutedContains("echo '* * * 1 1 bash -lc '\\''ls -la'\\''' | sudo -u vito crontab -");
    SSH::assertExecutedContains('sudo -u vito crontab -l');
});

test('enable site cronjob', function () {
    SSH::fake();

    $this->actingAs($this->user);

    /** @var CronJob $cronjob */
    $cronjob = CronJob::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $this->site->id,
        'user' => 'vito',
        'command' => 'ls -la',
        'frequency' => '* * * 1 1',
        'status' => CronjobStatus::DISABLED,
    ]);

    $this->post(route('cronjobs.site.enable', [
        'server' => $this->server,
        'site' => $this->site,
        'cronJob' => $cronjob,
    ]))
        ->assertSessionDoesntHaveErrors();

    $cronjob->refresh();

    expect($cronjob->status)->toEqual(CronjobStatus::READY);

    SSH::assertExecutedContains("echo '* * * 1 1 bash -lc '\\''ls -la'\\''' | sudo -u vito crontab -");
    SSH::assertExecutedContains('sudo -u vito crontab -l');
});

test('disable site cronjob', function () {
    SSH::fake();

    $this->actingAs($this->user);

    /** @var CronJob $cronjob */
    $cronjob = CronJob::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $this->site->id,
        'user' => 'vito',
        'command' => 'ls -la',
        'frequency' => '* * * 1 1',
        'status' => CronjobStatus::READY,
    ]);

    $this->post(route('cronjobs.site.disable', [
        'server' => $this->server,
        'site' => $this->site,
        'cronJob' => $cronjob,
    ]))
        ->assertSessionDoesntHaveErrors();

    $cronjob->refresh();

    expect($cronjob->status)->toEqual(CronjobStatus::DISABLED);

    SSH::assertExecutedContains("echo '' | sudo -u vito crontab -");
    SSH::assertExecutedContains('sudo -u vito crontab -l');
});

test('update site cronjob', function () {
    SSH::fake();

    $this->actingAs($this->user);

    /** @var CronJob $cronjob */
    $cronjob = CronJob::factory()->create([
        'server_id' => $this->server->id,
        'site_id' => $this->site->id,
        'user' => 'vito',
        'command' => 'ls -la',
        'frequency' => '* * * * *',
        'status' => CronjobStatus::READY,
    ]);

    $this->put(route('cronjobs.site.update', [
        'server' => $this->server,
        'site' => $this->site,
        'cronJob' => $cronjob,
    ]), [
        'command' => 'php artisan schedule:run',
        'user' => 'vito',
        'frequency' => '0 * * * *',
    ])
        ->assertSessionDoesntHaveErrors();

    $cronjob->refresh();

    expect($cronjob->command)->toEqual('php artisan schedule:run');
    expect($cronjob->frequency)->toEqual('0 * * * *');

    SSH::assertExecutedContains("echo '0 * * * * bash -lc '\\''php artisan schedule:run'\\''' | sudo -u vito crontab -");
    SSH::assertExecutedContains('sudo -u vito crontab -l');
});
