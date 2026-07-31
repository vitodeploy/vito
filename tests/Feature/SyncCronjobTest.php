<?php

use App\Enums\CronjobStatus;
use App\Facades\SSH;
use App\Models\CronJob;
use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('sync cronjobs from server', function () {
    // Mock SSH to return some existing cronjobs
    SSH::fake("0 2 * * * /usr/bin/backup.sh\n0 4 * * * /usr/bin/cleanup.sh");

    $this->actingAs($this->user)
        ->post(route('cronjobs.sync', $this->server))
        ->assertRedirect()
        ->assertSessionHas('success', 'Cron jobs synced successfully.');

    // Check that cronjobs were created
    $serverCronJobs = CronJob::where('server_id', $this->server->id)->get();

    // Should have 4 cronjobs (2 for root user, 2 for vito user)
    expect($serverCronJobs)->toHaveCount(4);

    // Check that we have the expected commands
    expect($serverCronJobs->contains('command', '/usr/bin/backup.sh'))->toBeTrue();
    expect($serverCronJobs->contains('command', '/usr/bin/cleanup.sh'))->toBeTrue();
    expect($serverCronJobs->contains('frequency', '0 2 * * *'))->toBeTrue();
    expect($serverCronJobs->contains('frequency', '0 4 * * *'))->toBeTrue();

    // Check that we have cronjobs for both users
    $rootCronJobs = $serverCronJobs->where('user', 'root');
    $vitoCronJobs = $serverCronJobs->where('user', 'vito');
    expect($rootCronJobs)->toHaveCount(2);
    expect($vitoCronJobs)->toHaveCount(2);
});

test('sync skips existing cronjobs', function () {
    // Create an existing cronjob
    CronJob::factory()->create([
        'server_id' => $this->server->id,
        'user' => 'root',
        'command' => '/usr/bin/backup.sh',
        'frequency' => '0 2 * * *',
        'status' => CronjobStatus::READY,
    ]);

    // Mock SSH to return the same cronjob plus a new one
    SSH::fake("0 2 * * * /usr/bin/backup.sh\n0 4 * * * /usr/bin/cleanup.sh");

    $this->actingAs($this->user)
        ->post(route('cronjobs.sync', $this->server))
        ->assertRedirect()
        ->assertSessionHas('success', 'Cron jobs synced successfully.');

    // Should have 4 cronjobs (1 existing + 3 new ones from sync)
    $cronJobs = CronJob::where('server_id', $this->server->id)->get();
    expect($cronJobs)->toHaveCount(4);

    // Should have both the existing and new cronjob
    expect($cronJobs->contains('command', '/usr/bin/backup.sh'))->toBeTrue();
    expect($cronJobs->contains('command', '/usr/bin/cleanup.sh'))->toBeTrue();
});

test('sync handles empty crontab', function () {
    // Mock SSH to return empty crontab
    SSH::fake('');

    $this->actingAs($this->user)
        ->post(route('cronjobs.sync', $this->server))
        ->assertRedirect()
        ->assertSessionHas('success', 'Cron jobs synced successfully.');

    // Should not create any cronjobs
    $this->assertDatabaseCount('cron_jobs', 0);
});

test('sync skips comments and empty lines', function () {
    // Mock SSH to return crontab with comments and empty lines
    SSH::fake("# This is a comment\n\n0 2 * * * /usr/bin/backup.sh\n# Another comment\n\n0 4 * * * /usr/bin/cleanup.sh\n");

    $this->actingAs($this->user)
        ->post(route('cronjobs.sync', $this->server))
        ->assertRedirect()
        ->assertSessionHas('success', 'Cron jobs synced successfully.');

    // Should only create the actual cronjobs, not comments
    $cronJobs = CronJob::where('server_id', $this->server->id)->get();
    expect($cronJobs)->toHaveCount(4);

    expect($cronJobs->contains('command', '/usr/bin/backup.sh'))->toBeTrue();
    expect($cronJobs->contains('command', '/usr/bin/cleanup.sh'))->toBeTrue();
});

test('sync creates disabled cronjobs for commented entries', function () {
    // Mock SSH to return commented cronjobs
    SSH::fake("# 0 2 * * * /usr/bin/backup.sh\n# 0 4 * * * /usr/bin/cleanup.sh");

    $this->actingAs($this->user)
        ->post(route('cronjobs.sync', $this->server))
        ->assertRedirect()
        ->assertSessionHas('success', 'Cron jobs synced successfully.');

    // Should create 4 cronjobs (2 for root, 2 for vito) and they should be disabled
    $cronJobs = CronJob::where('server_id', $this->server->id)->get();
    expect($cronJobs)->toHaveCount(4);

    // All should be disabled
    foreach ($cronJobs as $cronJob) {
        expect($cronJob->status)->toEqual(CronjobStatus::DISABLED);
    }

    expect($cronJobs->contains('command', '/usr/bin/backup.sh'))->toBeTrue();
    expect($cronJobs->contains('command', '/usr/bin/cleanup.sh'))->toBeTrue();
});

test('sync updates existing cronjobs based on comment status', function () {
    // Create an existing enabled cronjob
    $existingCronJob = CronJob::factory()->create([
        'server_id' => $this->server->id,
        'user' => 'root',
        'command' => '/usr/bin/backup.sh',
        'frequency' => '0 2 * * *',
        'status' => CronjobStatus::READY,
        'site_id' => null,
    ]);

    // Mock SSH to return the same cronjob but commented (disabled)
    SSH::fake('# 0 2 * * * /usr/bin/backup.sh');

    $this->actingAs($this->user)
        ->post(route('cronjobs.sync', $this->server))
        ->assertRedirect()
        ->assertSessionHas('success', 'Cron jobs synced successfully.');

    // The existing cronjob should now be disabled
    $existingCronJob->refresh();
    expect($existingCronJob->status)->toEqual(CronjobStatus::DISABLED);
});

test('sync enables existing disabled cronjobs when uncommented', function () {
    // Create an existing disabled cronjob
    $existingCronJob = CronJob::factory()->create([
        'server_id' => $this->server->id,
        'user' => 'root',
        'command' => '/usr/bin/backup.sh',
        'frequency' => '0 2 * * *',
        'status' => CronjobStatus::DISABLED,
        'site_id' => null,
    ]);

    // Mock SSH to return the same cronjob but uncommented (enabled)
    SSH::fake('0 2 * * * /usr/bin/backup.sh');

    $this->actingAs($this->user)
        ->post(route('cronjobs.sync', $this->server))
        ->assertRedirect()
        ->assertSessionHas('success', 'Cron jobs synced successfully.');

    // The existing cronjob should now be enabled
    $existingCronJob->refresh();
    expect($existingCronJob->status)->toEqual(CronjobStatus::READY);
});

test('sync handles mixed commented and uncommented cronjobs', function () {
    // Mock SSH to return mix of commented and uncommented cronjobs
    SSH::fake("0 2 * * * /usr/bin/backup.sh\n# 0 4 * * * /usr/bin/cleanup.sh");

    $this->actingAs($this->user)
        ->post(route('cronjobs.sync', $this->server))
        ->assertRedirect()
        ->assertSessionHas('success', 'Cron jobs synced successfully.');

    // Should create 4 cronjobs (2 for root, 2 for vito)
    $cronJobs = CronJob::where('server_id', $this->server->id)->get();
    expect($cronJobs)->toHaveCount(4);

    // Check that backup.sh is enabled and cleanup.sh is disabled
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
    // Create a Vito-managed cronjob
    $vitoCronJob = CronJob::factory()->create([
        'server_id' => $this->server->id,
        'user' => 'root',
        'command' => '/usr/bin/backup.sh',
        'frequency' => '0 2 * * *',
        'status' => CronjobStatus::READY,
        'site_id' => null,
    ]);

    // Mock SSH to return empty crontab (cronjob was manually deleted)
    SSH::fake('');

    $this->actingAs($this->user)
        ->post(route('cronjobs.sync', $this->server))
        ->assertRedirect()
        ->assertSessionHas('success', 'Cron jobs synced successfully.');

    // The Vito cronjob should be marked as disabled
    $vitoCronJob->refresh();
    expect($vitoCronJob->status)->toEqual(CronjobStatus::DISABLED);
});

test('sync disables vito cronjobs not found on server', function () {
    // Create multiple Vito-managed cronjobs
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

    // Mock SSH to return only one cronjob (the other was manually deleted)
    SSH::fake('0 2 * * * /usr/bin/backup.sh');

    $this->actingAs($this->user)
        ->post(route('cronjobs.sync', $this->server))
        ->assertRedirect()
        ->assertSessionHas('success', 'Cron jobs synced successfully.');

    // The first cronjob should remain enabled, the second should be disabled
    $cronJob1->refresh();
    $cronJob2->refresh();

    expect($cronJob1->status)->toEqual(CronjobStatus::READY);
    expect($cronJob2->status)->toEqual(CronjobStatus::DISABLED);
});

test('sync does not affect site level cronjobs', function () {
    // Create a site-level cronjob
    $siteCronJob = CronJob::factory()->create([
        'server_id' => $this->server->id,
        'user' => 'root',
        'command' => '/usr/bin/site-script.sh',
        'frequency' => '0 4 * * *',
        'status' => CronjobStatus::READY,
        'site_id' => $this->site->id, // Site-level
    ]);

    // Create a server-level cronjob
    $serverCronJob = CronJob::factory()->create([
        'server_id' => $this->server->id,
        'user' => 'root',
        'command' => '/usr/bin/server-script.sh',
        'frequency' => '0 2 * * *',
        'status' => CronjobStatus::READY,
        'site_id' => null, // Server-level
    ]);

    // Mock SSH to return empty crontab
    SSH::fake('');

    $this->actingAs($this->user)
        ->post(route('cronjobs.sync', $this->server))
        ->assertRedirect()
        ->assertSessionHas('success', 'Cron jobs synced successfully.');

    // Site-level cronjob should remain unchanged
    $siteCronJob->refresh();
    expect($siteCronJob->status)->toEqual(CronjobStatus::READY);

    // Server-level cronjob should be disabled
    $serverCronJob->refresh();
    expect($serverCronJob->status)->toEqual(CronjobStatus::DISABLED);
});

test('sync handles mixed scenarios with deletions', function () {
    // Create Vito-managed cronjobs
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

    // Mock SSH to return one existing cronjob, one commented, and one new manual cronjob
    SSH::fake("0 2 * * * /usr/bin/backup.sh\n# 0 4 * * * /usr/bin/cleanup.sh\n0 6 * * * /usr/bin/new-script.sh");

    $this->actingAs($this->user)
        ->post(route('cronjobs.sync', $this->server))
        ->assertRedirect()
        ->assertSessionHas('success', 'Cron jobs synced successfully.');

    // Check results
    $cronJob1->refresh();
    $cronJob2->refresh();

    // First cronjob should remain enabled
    expect($cronJob1->status)->toEqual(CronjobStatus::READY);

    // Second cronjob should be disabled (commented on server)
    expect($cronJob2->status)->toEqual(CronjobStatus::DISABLED);

    // New manual cronjob should be created
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
    // Create a cronjob with normal spacing
    $existingCronJob = CronJob::factory()->create([
        'server_id' => $this->server->id,
        'user' => 'root',
        'command' => '/usr/bin/backup.sh',
        'frequency' => '5 15 * * *',
        'status' => CronjobStatus::READY,
        'site_id' => null,
    ]);

    // Mock SSH to return the same cronjob with extra spaces
    SSH::fake('5  15   *    *  * /usr/bin/backup.sh');

    $this->actingAs($this->user)
        ->post(route('cronjobs.sync', $this->server))
        ->assertRedirect()
        ->assertSessionHas('success', 'Cron jobs synced successfully.');

    // Should not create duplicate, existing cronjob should remain
    $cronJobs = CronJob::where('server_id', $this->server->id)
        ->where('command', '/usr/bin/backup.sh')
        ->where('site_id', null)
        ->get();

    // Should only have the one existing cronjob for each user (root + vito = 2 total)
    expect($cronJobs)->toHaveCount(2);

    // The original cronjob should still be ready
    $existingCronJob->refresh();
    expect($existingCronJob->status)->toEqual(CronjobStatus::READY);
});

test('sync recognizes site level cronjobs', function () {
    // Create a site-level cronjob with the same command as what will be on the server
    $siteCronJob = CronJob::factory()->create([
        'server_id' => $this->server->id,
        'user' => 'root',
        'command' => '/usr/bin/backup.sh',
        'frequency' => '5 15 * * *',
        'status' => CronjobStatus::READY,
        'site_id' => $this->site->id,
    ]);

    // Mock SSH to return a cronjob with the same frequency and command
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

    // Before fix: would create duplicate with site_id = null
    // After fix: recognizes site-level cronjob and doesn't duplicate it, only creates for vito user
    // countBefore = 1 (site-level), countAfter should be 2 (site-level + vito user)
    expect($countAfter)->toEqual($countBefore + 1);

    // The site-level cronjob should remain unchanged
    $siteCronJob->refresh();
    expect($siteCronJob->site_id)->toEqual($this->site->id);
    expect($siteCronJob->status)->toEqual(CronjobStatus::READY);
});

test('sync handles frequency with mixed spacing in db', function () {
    // Create a cronjob with extra spaces in the frequency (simulating old data)
    $existingCronJob = CronJob::factory()->create([
        'server_id' => $this->server->id,
        'user' => 'root',
        'command' => '/usr/bin/backup.sh',
        'frequency' => '5  15  *  *  *', // Double spaces
        'status' => CronjobStatus::READY,
        'site_id' => null,
    ]);

    // Mock SSH to return the same cronjob with normalized spacing
    SSH::fake('5 15 * * * /usr/bin/backup.sh');

    $this->actingAs($this->user)
        ->post(route('cronjobs.sync', $this->server))
        ->assertRedirect()
        ->assertSessionHas('success', 'Cron jobs synced successfully.');

    // Should not create duplicate
    $cronJobs = CronJob::where('server_id', $this->server->id)
        ->where('command', '/usr/bin/backup.sh')
        ->where('site_id', null)
        ->get();

    // Should only have the one existing cronjob for each user (root + vito = 2 total)
    expect($cronJobs)->toHaveCount(2);

    // The original cronjob should still be ready
    $existingCronJob->refresh();
    expect($existingCronJob->status)->toEqual(CronjobStatus::READY);
});

test('sync ignores crontab documentation comments', function () {
    // Mock SSH to return crontab with documentation comments (like the default crontab header)
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

    // Should only create cronjobs for the actual cron line, not the documentation comments
    $cronJobs = CronJob::where('server_id', $this->server->id)->get();

    // Should have 2 cronjobs (1 for root, 1 for vito), not 6 (which would include the comment lines)
    expect($cronJobs)->toHaveCount(2);

    // Both should have the actual backup command
    expect($cronJobs->every(fn ($cronJob) => $cronJob->command === '/usr/bin/backup.sh'))->toBeTrue();
});

test('sync normalizes command with extra spaces', function () {
    // Create a cronjob with normal spacing in command
    $existingCronJob = CronJob::factory()->create([
        'server_id' => $this->server->id,
        'user' => 'root',
        'command' => 'ls -la',
        'frequency' => '* * * * *',
        'status' => CronjobStatus::READY,
        'site_id' => null,
    ]);

    // Mock SSH to return the same cronjob with extra spaces in command
    SSH::fake('* * *  * * ls  -la');

    $this->actingAs($this->user)
        ->post(route('cronjobs.sync', $this->server))
        ->assertRedirect()
        ->assertSessionHas('success', 'Cron jobs synced successfully.');

    // Should not create duplicate, existing cronjob should remain
    $cronJobs = CronJob::where('server_id', $this->server->id)
        ->where('site_id', null)
        ->get();

    // Should only have the one existing cronjob for each user (root + vito = 2 total)
    expect($cronJobs)->toHaveCount(2);

    // The original cronjob should still be ready
    $existingCronJob->refresh();
    expect($existingCronJob->status)->toEqual(CronjobStatus::READY);
    expect($existingCronJob->command)->toEqual('ls -la');
});
