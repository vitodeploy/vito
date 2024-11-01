<?php

namespace Tests\Feature\API;

use App\Enums\CronjobStatus;
use App\Facades\SSH;
use App\Models\CronJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CronjobTest extends TestCase
{
    use RefreshDatabase;

    public function test_see_cronjobs_list()
    {
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
    }

    public function test_create_cronjob()
    {
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
    }

    public function test_delete_cronjob()
    {
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
    }
}
