<?php

use App\Enums\CronjobStatus;
use App\Facades\SSH;
use App\Models\CronJob;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('sync cronjobs from server', function () {
    SSH::fake("0 2 * * * /usr/bin/backup.sh\n0 4 * * * /usr/bin/cleanup.sh");

    $this->actingAs($this->user)
        ->post(route('cronjobs.sync', $this->server))
        ->assertRedirect()
        ->assertSessionHas('success', 'Cron jobs synced successfully.');

    $serverCronJobs = CronJob::where('server_id', $this->server->id)->get();

    expect($serverCronJobs)->toHaveCount(4);

    expect($serverCronJobs->contains('command', '/usr/bin/backup.sh'))->toBeTrue();
    expect($serverCronJobs->contains('command', '/usr/bin/cleanup.sh'))->toBeTrue();
    expect($serverCronJobs->contains('frequency', '0 2 * * *'))->toBeTrue();
    expect($serverCronJobs->contains('frequency', '0 4 * * *'))->toBeTrue();

    $rootCronJobs = $serverCronJobs->where('user', 'root');
    $vitoCronJobs = $serverCronJobs->where('user', 'vito');
    expect($rootCronJobs)->toHaveCount(2);
    expect($vitoCronJobs)->toHaveCount(2);
});

test('sync skips existing cronjobs', function () {
    CronJob::factory()->create([
        'server_id' => $this->server->id,
        'user' => 'root',
        'command' => '/usr/bin/backup.sh',
        'frequency' => '0 2 * * *',
        'status' => CronjobStatus::READY,
    ]);

    SSH::fake("0 2 * * * /usr/bin/backup.sh\n0 4 * * * /usr/bin/cleanup.sh");

    $this->actingAs($this->user)
        ->post(route('cronjobs.sync', $this->server))
        ->assertRedirect()
        ->assertSessionHas('success', 'Cron jobs synced successfully.');

    $cronJobs = CronJob::where('server_id', $this->server->id)->get();
    expect($cronJobs)->toHaveCount(4);

    expect($cronJobs->contains('command', '/usr/bin/backup.sh'))->toBeTrue();
    expect($cronJobs->contains('command', '/usr/bin/cleanup.sh'))->toBeTrue();
});

test('sync handles empty crontab', function () {
    SSH::fake('');

    $this->actingAs($this->user)
        ->post(route('cronjobs.sync', $this->server))
        ->assertRedirect()
        ->assertSessionHas('success', 'Cron jobs synced successfully.');

    $this->assertDatabaseCount('cron_jobs', 0);
});

test('sync skips comments and empty lines', function () {
    SSH::fake("# This is a comment\n\n0 2 * * * /usr/bin/backup.sh\n# Another comment\n\n0 4 * * * /usr/bin/cleanup.sh\n");

    $this->actingAs($this->user)
        ->post(route('cronjobs.sync', $this->server))
        ->assertRedirect()
        ->assertSessionHas('success', 'Cron jobs synced successfully.');

    $cronJobs = CronJob::where('server_id', $this->server->id)->get();
    expect($cronJobs)->toHaveCount(4);

    expect($cronJobs->contains('command', '/usr/bin/backup.sh'))->toBeTrue();
    expect($cronJobs->contains('command', '/usr/bin/cleanup.sh'))->toBeTrue();
});

test('sync creates disabled cronjobs for commented entries', function () {
    SSH::fake("# 0 2 * * * /usr/bin/backup.sh\n# 0 4 * * * /usr/bin/cleanup.sh");

    $this->actingAs($this->user)
        ->post(route('cronjobs.sync', $this->server))
        ->assertRedirect()
        ->assertSessionHas('success', 'Cron jobs synced successfully.');

    $cronJobs = CronJob::where('server_id', $this->server->id)->get();
    expect($cronJobs)->toHaveCount(4);

    foreach ($cronJobs as $cronJob) {
        expect($cronJob->status)->toEqual(CronjobStatus::DISABLED);
    }

    expect($cronJobs->contains('command', '/usr/bin/backup.sh'))->toBeTrue();
    expect($cronJobs->contains('command', '/usr/bin/cleanup.sh'))->toBeTrue();
});

test('sync updates existing cronjobs based on comment status', function () {
    $existingCronJob = CronJob::factory()->create([
        'server_id' => $this->server->id,
        'user' => 'root',
        'command' => '/usr/bin/backup.sh',
        'frequency' => '0 2 * * *',
        'status' => CronjobStatus::READY,
        'site_id' => null,
    ]);

    SSH::fake('# 0 2 * * * /usr/bin/backup.sh');

    $this->actingAs($this->user)
        ->post(route('cronjobs.sync', $this->server))
        ->assertRedirect()
        ->assertSessionHas('success', 'Cron jobs synced successfully.');

    $existingCronJob->refresh();
    expect($existingCronJob->status)->toEqual(CronjobStatus::DISABLED);
});

test('sync enables existing disabled cronjobs when uncommented', function () {
    $existingCronJob = CronJob::factory()->create([
        'server_id' => $this->server->id,
        'user' => 'root',
        'command' => '/usr/bin/backup.sh',
        'frequency' => '0 2 * * *',
        'status' => CronjobStatus::DISABLED,
        'site_id' => null,
    ]);

    SSH::fake('0 2 * * * /usr/bin/backup.sh');

    $this->actingAs($this->user)
        ->post(route('cronjobs.sync', $this->server))
        ->assertRedirect()
        ->assertSessionHas('success', 'Cron jobs synced successfully.');

    $existingCronJob->refresh();
    expect($existingCronJob->status)->toEqual(CronjobStatus::READY);
});

test('sync handles mixed commented and uncommented cronjobs', function () {
    SSH::fake("0 2 * * * /usr/bin/backup.sh\n# 0 4 * * * /usr/bin/cleanup.sh");

    $this->actingAs($this->user)
        ->post(route('cronjobs.sync', $this->server))
        ->assertRedirect()
        ->assertSessionHas('success', 'Cron jobs synced successfully.');

    $cronJobs = CronJob::where('server_id', $this->server->id)->get();
    expect($cronJobs)->toHaveCount(4);

    $backupCronJobs = $cronJobs->where('command', '/usr/bin/backup.sh');
    $cleanupCronJobs = $cronJobs->where('command', '/usr/bin/cleanup.sh');

    foreach ($backupCronJobs as $cronJob) {
        expect($cronJob->status)->toEqual(CronjobStatus::READY);
    }

    foreach ($cleanupCronJobs as $cronJob) {
        expect($cronJob->status)->toEqual(CronjobStatus::DISABLED);
    }
});

test('sync disables vito cronjobs removed from server', function () {
    $vitoCronJob = CronJob::factory()->create([
        'server_id' => $this->server->id,
        'user' => 'root',
        'command' => '/usr/bin/backup.sh',
        'frequency' => '0 2 * * *',
        'status' => CronjobStatus::READY,
        'site_id' => null,
    ]);

    SSH::fake('');

    $this->actingAs($this->user)
        ->post(route('cronjobs.sync', $this->server))
        ->assertRedirect()
        ->assertSessionHas('success', 'Cron jobs synced successfully.');

    $vitoCronJob->refresh();
    expect($vitoCronJob->status)->toEqual(CronjobStatus::DISABLED);
});

test('sync disables vito cronjobs not found on server', function () {
    $cronJob1 = CronJob::factory()->create([
        'server_id' => $this->server->id,
        'user' => 'root',
        'command' => '/usr/bin/backup.sh',
        'frequency' => '0 2 * * *',
        'status' => CronjobStatus::READY,
        'site_id' => null,
    ]);

    $cronJob2 = CronJob::factory()->create([
        'server_id' => $this->server->id,
        'user' => 'root',
        'command' => '/usr/bin/cleanup.sh',
        'frequency' => '0 4 * * *',
        'status' => CronjobStatus::READY,
        'site_id' => null,
    ]);

    SSH::fake('0 2 * * * /usr/bin/backup.sh');

    $this->actingAs($this->user)
        ->post(route('cronjobs.sync', $this->server))
        ->assertRedirect()
        ->assertSessionHas('success', 'Cron jobs synced successfully.');

    $cronJob1->refresh();
    $cronJob2->refresh();

    expect($cronJob1->status)->toEqual(CronjobStatus::READY);
    expect($cronJob2->status)->toEqual(CronjobStatus::DISABLED);
});

test('sync does not affect site level cronjobs', function () {
    $siteCronJob = CronJob::factory()->create([
        'server_id' => $this->server->id,
        'user' => 'root',
        'command' => '/usr/bin/site-script.sh',
        'frequency' => '0 4 * * *',
        'status' => CronjobStatus::READY,
        'site_id' => $this->site->id,
    ]);

    $serverCronJob = CronJob::factory()->create([
        'server_id' => $this->server->id,
        'user' => 'root',
        'command' => '/usr/bin/server-script.sh',
        'frequency' => '0 2 * * *',
        'status' => CronjobStatus::READY,
        'site_id' => null,
    ]);

    SSH::fake('');

    $this->actingAs($this->user)
        ->post(route('cronjobs.sync', $this->server))
        ->assertRedirect()
        ->assertSessionHas('success', 'Cron jobs synced successfully.');

    $siteCronJob->refresh();
    expect($siteCronJob->status)->toEqual(CronjobStatus::READY);

    $serverCronJob->refresh();
    expect($serverCronJob->status)->toEqual(CronjobStatus::DISABLED);
});

test('sync handles mixed scenarios with deletions', function () {
    $cronJob1 = CronJob::factory()->create([
        'server_id' => $this->server->id,
        'user' => 'root',
        'command' => '/usr/bin/backup.sh',
        'frequency' => '0 2 * * *',
        'status' => CronjobStatus::READY,
        'site_id' => null,
    ]);

    $cronJob2 = CronJob::factory()->create([
        'server_id' => $this->server->id,
        'user' => 'root',
        'command' => '/usr/bin/cleanup.sh',
        'frequency' => '0 4 * * *',
        'status' => CronjobStatus::READY,
        'site_id' => null,
    ]);

    SSH::fake("0 2 * * * /usr/bin/backup.sh\n# 0 4 * * * /usr/bin/cleanup.sh\n0 6 * * * /usr/bin/new-script.sh");

    $this->actingAs($this->user)
        ->post(route('cronjobs.sync', $this->server))
        ->assertRedirect()
        ->assertSessionHas('success', 'Cron jobs synced successfully.');

    $cronJob1->refresh();
    $cronJob2->refresh();

    expect($cronJob1->status)->toEqual(CronjobStatus::READY);

    expect($cronJob2->status)->toEqual(CronjobStatus::DISABLED);

    $this->assertDatabaseHas('cron_jobs', [
        'server_id' => $this->server->id,
        'user' => 'root',
        'command' => '/usr/bin/new-script.sh',
        'frequency' => '0 6 * * *',
        'status' => CronjobStatus::READY,
        'site_id' => null,
    ]);
});

test('sync normalizes frequency with extra spaces', function () {
    $existingCronJob = CronJob::factory()->create([
        'server_id' => $this->server->id,
        'user' => 'root',
        'command' => '/usr/bin/backup.sh',
        'frequency' => '5 15 * * *',
        'status' => CronjobStatus::READY,
        'site_id' => null,
    ]);

    SSH::fake('5  15   *    *  * /usr/bin/backup.sh');

    $this->actingAs($this->user)
        ->post(route('cronjobs.sync', $this->server))
        ->assertRedirect()
        ->assertSessionHas('success', 'Cron jobs synced successfully.');

    $cronJobs = CronJob::where('server_id', $this->server->id)
        ->where('command', '/usr/bin/backup.sh')
        ->where('site_id', null)
        ->get();

    expect($cronJobs)->toHaveCount(2);

    $existingCronJob->refresh();
    expect($existingCronJob->status)->toEqual(CronjobStatus::READY);
});

test('sync recognizes site level cronjobs', function () {
    $siteCronJob = CronJob::factory()->create([
        'server_id' => $this->server->id,
        'user' => 'root',
        'command' => '/usr/bin/backup.sh',
        'frequency' => '5 15 * * *',
        'status' => CronjobStatus::READY,
        'site_id' => $this->site->id,
    ]);

    SSH::fake('5 15 * * * /usr/bin/backup.sh');

    $countBefore = CronJob::where('server_id', $this->server->id)
        ->where('command', '/usr/bin/backup.sh')
        ->count();

    $this->actingAs($this->user)
        ->post(route('cronjobs.sync', $this->server))
        ->assertRedirect()
        ->assertSessionHas('success', 'Cron jobs synced successfully.');

    $countAfter = CronJob::where('server_id', $this->server->id)
        ->where('command', '/usr/bin/backup.sh')
        ->count();

    expect($countAfter)->toEqual($countBefore + 1);

    $siteCronJob->refresh();
    expect($siteCronJob->site_id)->toEqual($this->site->id);
    expect($siteCronJob->status)->toEqual(CronjobStatus::READY);
});

test('sync handles frequency with mixed spacing in db', function () {
    $existingCronJob = CronJob::factory()->create([
        'server_id' => $this->server->id,
        'user' => 'root',
        'command' => '/usr/bin/backup.sh',
        'frequency' => '5  15  *  *  *',
        'status' => CronjobStatus::READY,
        'site_id' => null,
    ]);

    SSH::fake('5 15 * * * /usr/bin/backup.sh');

    $this->actingAs($this->user)
        ->post(route('cronjobs.sync', $this->server))
        ->assertRedirect()
        ->assertSessionHas('success', 'Cron jobs synced successfully.');

    $cronJobs = CronJob::where('server_id', $this->server->id)
        ->where('command', '/usr/bin/backup.sh')
        ->where('site_id', null)
        ->get();

    expect($cronJobs)->toHaveCount(2);

    $existingCronJob->refresh();
    expect($existingCronJob->status)->toEqual(CronjobStatus::READY);
});

test('sync ignores crontab documentation comments', function () {
    $crontabWithComments = '# Edit this file to introduce tasks to be run by cron.
#
# Each task to run has to be defined through a single line
# m h  dom mon dow   command
#
0 2 * * * /usr/bin/backup.sh';

    SSH::fake($crontabWithComments);

    $this->actingAs($this->user)
        ->post(route('cronjobs.sync', $this->server))
        ->assertRedirect()
        ->assertSessionHas('success', 'Cron jobs synced successfully.');

    $cronJobs = CronJob::where('server_id', $this->server->id)->get();

    expect($cronJobs)->toHaveCount(2);

    expect($cronJobs->every(fn ($cronJob) => $cronJob->command === '/usr/bin/backup.sh'))->toBeTrue();
});

test('sync normalizes command with extra spaces', function () {
    $existingCronJob = CronJob::factory()->create([
        'server_id' => $this->server->id,
        'user' => 'root',
        'command' => 'ls -la',
        'frequency' => '* * * * *',
        'status' => CronjobStatus::READY,
        'site_id' => null,
    ]);

    SSH::fake('* * *  * * ls  -la');

    $this->actingAs($this->user)
        ->post(route('cronjobs.sync', $this->server))
        ->assertRedirect()
        ->assertSessionHas('success', 'Cron jobs synced successfully.');

    $cronJobs = CronJob::where('server_id', $this->server->id)
        ->where('site_id', null)
        ->get();

    expect($cronJobs)->toHaveCount(2);

    $existingCronJob->refresh();
    expect($existingCronJob->status)->toEqual(CronjobStatus::READY);
    expect($existingCronJob->command)->toEqual('ls -la');
});
