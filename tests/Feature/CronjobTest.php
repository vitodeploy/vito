<?php

namespace Tests\Feature;

use App\Enums\CronjobStatus;
use App\Facades\SSH;
use App\Models\CronJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CronjobTest extends TestCase
{
    use RefreshDatabase;

    public function test_see_cronjobs_list()
    {
        $this->actingAs($this->user);

        /** @var CronJob $cronjob */
        $cronjob = CronJob::factory()->create([
            'server_id' => $this->server->id,
        ]);

        $this->get(route('servers.cronjobs', $this->server))
            ->assertSuccessful()
            ->assertSeeText($cronjob->frequencyLabel());
    }

    public function test_delete_cronjob()
    {
        SSH::fake();

        $this->actingAs($this->user);

        /** @var CronJob $cronjob */
        $cronjob = CronJob::factory()->create([
            'server_id' => $this->server->id,
            'user' => 'vito',
        ]);

        $this->delete(route('servers.cronjobs.destroy', [
            'server' => $this->server,
            'cronJob' => $cronjob,
        ]))->assertSessionDoesntHaveErrors();

        $this->assertDatabaseMissing('cron_jobs', [
            'id' => $cronjob->id,
        ]);

        SSH::assertExecutedContains("echo '' | sudo -u vito crontab -");
        SSH::assertExecutedContains('sudo -u vito crontab -l');
    }

    public function test_create_cronjob()
    {
        SSH::fake();

        $this->actingAs($this->user);

        $this->post(route('servers.cronjobs.store', $this->server), [
            'command' => 'ls -la',
            'user' => 'vito',
            'frequency' => '* * * * *',
        ])->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('cron_jobs', [
            'server_id' => $this->server->id,
            'command' => 'ls -la',
            'user' => 'vito',
            'frequency' => '* * * * *',
            'status' => CronjobStatus::READY,
        ]);

        SSH::assertExecutedContains("echo '* * * * * ls -la' | sudo -u vito crontab -");
        SSH::assertExecutedContains('sudo -u vito crontab -l');
    }

    public function test_create_custom_cronjob()
    {
        SSH::fake();

        $this->actingAs($this->user);

        $this->post(route('servers.cronjobs.store', $this->server), [
            'command' => 'ls -la',
            'user' => 'vito',
            'frequency' => 'custom',
            'custom' => '* * * 1 1',
        ])->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('cron_jobs', [
            'server_id' => $this->server->id,
            'command' => 'ls -la',
            'user' => 'vito',
            'frequency' => '* * * 1 1',
            'status' => CronjobStatus::READY,
        ]);

        SSH::assertExecutedContains("echo '* * * 1 1 ls -la' | sudo -u vito crontab -");
        SSH::assertExecutedContains('sudo -u vito crontab -l');
    }

    public function test_enable_cronjob()
    {
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

        $this->post(route('servers.cronjobs.enable', [
            'server' => $this->server,
            'cronJob' => $cronjob,
        ]))->assertSessionDoesntHaveErrors();

        $cronjob->refresh();

        $this->assertEquals(CronjobStatus::READY, $cronjob->status);

        SSH::assertExecutedContains("echo '* * * 1 1 ls -la' | sudo -u vito crontab -");
        SSH::assertExecutedContains('sudo -u vito crontab -l');
    }

    public function test_disable_cronjob()
    {
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

        $this->post(route('servers.cronjobs.disable', [
            'server' => $this->server,
            'cronJob' => $cronjob,
        ]))->assertSessionDoesntHaveErrors();

        $cronjob->refresh();

        $this->assertEquals(CronjobStatus::DISABLED, $cronjob->status);

        SSH::assertExecutedContains("echo '' | sudo -u vito crontab -");
        SSH::assertExecutedContains('sudo -u vito crontab -l');
    }
}
