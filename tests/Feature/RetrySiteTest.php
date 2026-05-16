<?php

namespace Tests\Feature;

use App\Enums\SiteStatus;
use App\Facades\SSH;
use App\Jobs\Site\CreateJob;
use App\Models\Site;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RetrySiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_retry_failed_site_resets_state_and_dispatches_create_job(): void
    {
        Queue::fake();
        $this->actingAs($this->user);

        $site = Site::factory()->create([
            'server_id' => $this->server->id,
            'status' => SiteStatus::INSTALLATION_FAILED,
            'progress' => 40,
            'progress_step' => 'cloning-repository',
            'last_error' => '[SSHCommandError] SSH command failed with an error',
        ]);

        $this->post(route('sites.retry', ['server' => $this->server, 'site' => $site]))
            ->assertSessionDoesntHaveErrors()
            ->assertRedirect();

        $site->refresh();
        $this->assertEquals(SiteStatus::INSTALLING, $site->status);
        $this->assertNull($site->last_error);
        $this->assertNull($site->progress_step);
        $this->assertEquals(0, $site->progress);

        Queue::assertPushedOn('ssh', CreateJob::class);
    }

    public function test_retry_rejects_site_that_is_not_failed(): void
    {
        $this->actingAs($this->user);

        $site = Site::factory()->create([
            'server_id' => $this->server->id,
            'status' => SiteStatus::READY,
        ]);

        $this->post(route('sites.retry', ['server' => $this->server, 'site' => $site]))
            ->assertSessionHasErrors(['status']);

        $site->refresh();
        $this->assertEquals(SiteStatus::READY, $site->status);
    }

    public function test_create_job_failed_populates_last_error(): void
    {
        Notification::fake();
        SSH::fake();

        $site = Site::factory()->create([
            'server_id' => $this->server->id,
            'status' => SiteStatus::INSTALLING,
        ]);

        $job = new CreateJob($site);
        $job->failed(new Exception('something went wrong'));

        $site->refresh();
        $this->assertEquals(SiteStatus::INSTALLATION_FAILED, $site->status);
        $this->assertNotNull($site->last_error);
        $this->assertStringContainsString('Exception', $site->last_error);
        $this->assertStringContainsString('something went wrong', $site->last_error);

        $this->assertDatabaseHas('server_logs', [
            'server_id' => $this->server->id,
            'site_id' => $site->id,
            'type' => 'site-installation-failed',
        ]);
    }
}
