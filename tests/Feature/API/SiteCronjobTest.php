<?php

namespace Tests\Feature\API;

use App\Enums\CronjobStatus;
use App\Facades\SSH;
use App\Models\CronJob;
use App\Models\Server;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SiteCronjobTest extends TestCase
{
    use RefreshDatabase;

    public function test_see_site_cronjobs_list(): void
    {
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
    }

    public function test_see_site_cronjob(): void
    {
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
    }

    public function test_create_site_cronjob(): void
    {
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
        ]);
    }

    public function test_create_site_cronjob_for_isolated_user(): void
    {
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
    }

    public function test_cannot_create_site_cronjob_for_non_existing_user(): void
    {
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
    }

    public function test_delete_site_cronjob(): void
    {
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
    }

    public function test_cannot_access_cronjob_from_different_site(): void
    {
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
    }

    public function test_cannot_access_cronjob_from_different_server(): void
    {
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
    }
}
