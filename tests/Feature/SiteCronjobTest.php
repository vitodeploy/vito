<?php

namespace Tests\Feature;

use App\Enums\CronjobStatus;
use App\Facades\SSH;
use App\Models\CronJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class SiteCronjobTest extends TestCase
{
    use RefreshDatabase;

    public function test_see_site_cronjobs_list(): void
    {
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
    }

    public function test_delete_site_cronjob(): void
    {
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
    }

    public function test_create_site_cronjob(): void
    {
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

        SSH::assertExecutedContains("echo '* * * * * ls -la' | sudo -u vito crontab -");
        SSH::assertExecutedContains('sudo -u vito crontab -l');
    }

    public function test_create_site_cronjob_for_isolated_user(): void
    {
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

        SSH::assertExecutedContains("echo '* * * * * ls -la' | sudo -u example crontab -");
        SSH::assertExecutedContains('sudo -u example crontab -l');
    }

    public function test_cannot_create_site_cronjob_for_non_existing_user(): void
    {
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
    }

    public function test_create_custom_site_cronjob(): void
    {
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

        SSH::assertExecutedContains("echo '* * * 1 1 ls -la' | sudo -u vito crontab -");
        SSH::assertExecutedContains('sudo -u vito crontab -l');
    }

    public function test_enable_site_cronjob(): void
    {
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

        $this->assertEquals(CronjobStatus::READY, $cronjob->status);

        SSH::assertExecutedContains("echo '* * * 1 1 ls -la' | sudo -u vito crontab -");
        SSH::assertExecutedContains('sudo -u vito crontab -l');
    }

    public function test_disable_site_cronjob(): void
    {
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

        $this->assertEquals(CronjobStatus::DISABLED, $cronjob->status);

        SSH::assertExecutedContains("echo '' | sudo -u vito crontab -");
        SSH::assertExecutedContains('sudo -u vito crontab -l');
    }

    public function test_update_site_cronjob(): void
    {
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

        $this->assertEquals('php artisan schedule:run', $cronjob->command);
        $this->assertEquals('0 * * * *', $cronjob->frequency);

        SSH::assertExecutedContains("echo '0 * * * * php artisan schedule:run' | sudo -u vito crontab -");
        SSH::assertExecutedContains('sudo -u vito crontab -l');
    }
}
