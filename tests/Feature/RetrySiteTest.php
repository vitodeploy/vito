<?php

namespace Tests\Feature;

use App\Enums\SiteStatus;
use App\Exceptions\FailedToDeployGitKey;
use App\Facades\SSH;
use App\Jobs\Site\CreateJob;
use App\Models\Site;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RetrySiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_retry_failed_site_resets_state_and_dispatches_create_job(): void
    {
        Event::fake();
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

    public function test_create_job_failed_populates_safe_last_error_and_preserves_progress_step(): void
    {
        Notification::fake();
        SSH::fake();

        $site = Site::factory()->create([
            'server_id' => $this->server->id,
            'status' => SiteStatus::INSTALLING,
            'progress' => 60,
            'progress_step' => 'cloning-repository',
        ]);

        $job = new CreateJob($site);
        $job->failed(new Exception('raw provider response with possibly-sensitive payload'));

        $site->refresh();
        $this->assertEquals(SiteStatus::INSTALLATION_FAILED, $site->status);
        $this->assertNotNull($site->last_error);
        $this->assertStringNotContainsString('raw provider response', $site->last_error);
        $this->assertStringContainsString('Installation failed', $site->last_error);
        $this->assertEquals('cloning-repository', $site->progress_step);

        $this->assertDatabaseHas('server_logs', [
            'server_id' => $this->server->id,
            'site_id' => $site->id,
            'type' => 'site-installation-failed',
        ]);
    }

    public function test_create_job_failed_uses_friendly_message_for_known_exceptions(): void
    {
        Notification::fake();
        SSH::fake();

        $site = Site::factory()->create([
            'server_id' => $this->server->id,
            'status' => SiteStatus::INSTALLING,
        ]);

        $job = new CreateJob($site);
        $job->failed(new FailedToDeployGitKey('GitHub returned full key payload {"key": "ssh-rsa AAAA..."}'));

        $site->refresh();
        $this->assertNotNull($site->last_error);
        $this->assertStringNotContainsString('ssh-rsa', $site->last_error);
        $this->assertStringNotContainsString('AAAA', $site->last_error);
        $this->assertStringContainsString('deploy key', $site->last_error);
    }
}
