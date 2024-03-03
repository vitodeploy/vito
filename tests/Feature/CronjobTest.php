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
            ->assertSeeText($cronjob->frequency_label);
    }

    public function test_delete_cronjob()
    {
        SSH::fake();

        $this->actingAs($this->user);

        /** @var CronJob $cronjob */
        $cronjob = CronJob::factory()->create([
            'server_id' => $this->server->id,
        ]);

        $this->delete(route('servers.cronjobs.destroy', [
            'server' => $this->server,
            'cronJob' => $cronjob,
        ]))->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('cron_jobs', [
            'id' => $cronjob->id,
        ]);
    }

    public function test_create_cronjob()
    {
        SSH::fake();

        $this->actingAs($this->user);

        $this->post(route('servers.cronjobs.store', $this->server), [
            'command' => 'ls -la',
            'user' => 'vito',
            'frequency' => '* * * * *',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('cron_jobs', [
            'server_id' => $this->server->id,
            'command' => 'ls -la',
            'user' => 'vito',
            'frequency' => '* * * * *',
            'status' => CronjobStatus::READY,
        ]);
    }
}
