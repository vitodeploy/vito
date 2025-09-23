<?php

namespace Tests\Feature;

use App\Enums\CronjobStatus;
use App\Facades\SSH;
use App\Models\CronJob;
use App\Models\Server;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SyncCronjobTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_cronjobs_from_server(): void
    {
        // Mock SSH to return some existing cronjobs
        SSH::fake("0 2 * * * /usr/bin/backup.sh\n0 4 * * * /usr/bin/cleanup.sh");

        $this->actingAs($this->user)
            ->post(route('cronjobs.sync', $this->server))
            ->assertRedirect()
            ->assertSessionHas('success', 'Cron jobs synced successfully.');

        // Check that cronjobs were created
        $serverCronJobs = CronJob::where('server_id', $this->server->id)->get();

        // Should have 4 cronjobs (2 for root user, 2 for vito user)
        $this->assertCount(4, $serverCronJobs);

        // Check that we have the expected commands
        $this->assertTrue($serverCronJobs->contains('command', '/usr/bin/backup.sh'));
        $this->assertTrue($serverCronJobs->contains('command', '/usr/bin/cleanup.sh'));
        $this->assertTrue($serverCronJobs->contains('frequency', '0 2 * * *'));
        $this->assertTrue($serverCronJobs->contains('frequency', '0 4 * * *'));

        // Check that we have cronjobs for both users
        $rootCronJobs = $serverCronJobs->where('user', 'root');
        $vitoCronJobs = $serverCronJobs->where('user', 'vito');
        $this->assertCount(2, $rootCronJobs);
        $this->assertCount(2, $vitoCronJobs);
    }

    public function test_sync_skips_existing_cronjobs(): void
    {
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
        $this->assertCount(4, $cronJobs);

        // Should have both the existing and new cronjob
        $this->assertTrue($cronJobs->contains('command', '/usr/bin/backup.sh'));
        $this->assertTrue($cronJobs->contains('command', '/usr/bin/cleanup.sh'));
    }

    public function test_sync_handles_empty_crontab(): void
    {
        // Mock SSH to return empty crontab
        SSH::fake('');

        $this->actingAs($this->user)
            ->post(route('cronjobs.sync', $this->server))
            ->assertRedirect()
            ->assertSessionHas('success', 'Cron jobs synced successfully.');

        // Should not create any cronjobs
        $this->assertDatabaseCount('cron_jobs', 0);
    }

    public function test_sync_skips_comments_and_empty_lines(): void
    {
        // Mock SSH to return crontab with comments and empty lines
        SSH::fake("# This is a comment\n\n0 2 * * * /usr/bin/backup.sh\n# Another comment\n\n0 4 * * * /usr/bin/cleanup.sh\n");

        $this->actingAs($this->user)
            ->post(route('cronjobs.sync', $this->server))
            ->assertRedirect()
            ->assertSessionHas('success', 'Cron jobs synced successfully.');

        // Should only create the actual cronjobs, not comments
        $cronJobs = CronJob::where('server_id', $this->server->id)->get();
        $this->assertCount(4, $cronJobs);

        $this->assertTrue($cronJobs->contains('command', '/usr/bin/backup.sh'));
        $this->assertTrue($cronJobs->contains('command', '/usr/bin/cleanup.sh'));
    }

    public function test_sync_creates_disabled_cronjobs_for_commented_entries(): void
    {
        // Mock SSH to return commented cronjobs
        SSH::fake("# 0 2 * * * /usr/bin/backup.sh\n# 0 4 * * * /usr/bin/cleanup.sh");

        $this->actingAs($this->user)
            ->post(route('cronjobs.sync', $this->server))
            ->assertRedirect()
            ->assertSessionHas('success', 'Cron jobs synced successfully.');

        // Should create 4 cronjobs (2 for root, 2 for vito) and they should be disabled
        $cronJobs = CronJob::where('server_id', $this->server->id)->get();
        $this->assertCount(4, $cronJobs);

        // All should be disabled
        foreach ($cronJobs as $cronJob) {
            $this->assertEquals(CronjobStatus::DISABLED, $cronJob->status);
        }

        $this->assertTrue($cronJobs->contains('command', '/usr/bin/backup.sh'));
        $this->assertTrue($cronJobs->contains('command', '/usr/bin/cleanup.sh'));
    }

    public function test_sync_updates_existing_cronjobs_based_on_comment_status(): void
    {
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
        $this->assertEquals(CronjobStatus::DISABLED, $existingCronJob->status);
    }

    public function test_sync_enables_existing_disabled_cronjobs_when_uncommented(): void
    {
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
        $this->assertEquals(CronjobStatus::READY, $existingCronJob->status);
    }

    public function test_sync_handles_mixed_commented_and_uncommented_cronjobs(): void
    {
        // Mock SSH to return mix of commented and uncommented cronjobs
        SSH::fake("0 2 * * * /usr/bin/backup.sh\n# 0 4 * * * /usr/bin/cleanup.sh");

        $this->actingAs($this->user)
            ->post(route('cronjobs.sync', $this->server))
            ->assertRedirect()
            ->assertSessionHas('success', 'Cron jobs synced successfully.');

        // Should create 4 cronjobs (2 for root, 2 for vito)
        $cronJobs = CronJob::where('server_id', $this->server->id)->get();
        $this->assertCount(4, $cronJobs);

        // Check that backup.sh is enabled and cleanup.sh is disabled
        $backupCronJobs = $cronJobs->where('command', '/usr/bin/backup.sh');
        $cleanupCronJobs = $cronJobs->where('command', '/usr/bin/cleanup.sh');

        foreach ($backupCronJobs as $cronJob) {
            $this->assertEquals(CronjobStatus::READY, $cronJob->status);
        }

        foreach ($cleanupCronJobs as $cronJob) {
            $this->assertEquals(CronjobStatus::DISABLED, $cronJob->status);
        }
    }

    public function test_sync_disables_vito_cronjobs_removed_from_server(): void
    {
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
        $this->assertEquals(CronjobStatus::DISABLED, $vitoCronJob->status);
    }

    public function test_sync_disables_vito_cronjobs_not_found_on_server(): void
    {
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

        $this->assertEquals(CronjobStatus::READY, $cronJob1->status);
        $this->assertEquals(CronjobStatus::DISABLED, $cronJob2->status);
    }

    public function test_sync_does_not_affect_site_level_cronjobs(): void
    {
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
        $this->assertEquals(CronjobStatus::READY, $siteCronJob->status);

        // Server-level cronjob should be disabled
        $serverCronJob->refresh();
        $this->assertEquals(CronjobStatus::DISABLED, $serverCronJob->status);
    }

    public function test_sync_handles_mixed_scenarios_with_deletions(): void
    {
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
        $this->assertEquals(CronjobStatus::READY, $cronJob1->status);

        // Second cronjob should be disabled (commented on server)
        $this->assertEquals(CronjobStatus::DISABLED, $cronJob2->status);

        // New manual cronjob should be created
        $this->assertDatabaseHas('cron_jobs', [
            'server_id' => $this->server->id,
            'user' => 'root',
            'command' => '/usr/bin/new-script.sh',
            'frequency' => '0 6 * * *',
            'status' => CronjobStatus::READY,
            'site_id' => null,
        ]);
    }
}
