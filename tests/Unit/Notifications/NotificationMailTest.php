<?php

namespace Tests\Unit\Notifications;

use App\Enums\DeploymentStatus;
use App\Mail\ProjectInvitation;
use App\Models\Backup;
use App\Models\Database;
use App\Models\Deployment;
use App\Models\Project;
use App\Models\StorageProvider;
use App\Notifications\BackupFailed;
use App\Notifications\DeploymentCompleted;
use App\Notifications\ServerInstallationFailed;
use App\Notifications\ServerInstallationSucceed;
use App\Notifications\SiteInstallationFailed;
use App\Notifications\WebhookDeploymentFailed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_server_installation_succeed_links_to_server_with_success_level(): void
    {
        $message = (new ServerInstallationSucceed($this->server))->toEmail($this->notificationChannel);

        $this->assertSame('success', $message->level);
        $this->assertSame(url('/servers/'.$this->server->id), $message->actionUrl);
        $this->assertStringNotContainsString('/login', (string) $message->actionUrl);
    }

    public function test_failure_notification_uses_error_level(): void
    {
        $message = (new ServerInstallationFailed($this->server))->toEmail($this->notificationChannel);

        $this->assertSame('error', $message->level);
    }

    public function test_rendered_email_contains_vito_branding(): void
    {
        $html = (new ServerInstallationSucceed($this->server))->toEmail($this->notificationChannel)->render();

        $this->assertStringContainsString('email/logo.png', $html);
        $this->assertStringContainsString('Sent by', $html);
    }

    public function test_site_installation_failed_links_to_site_with_retry_message(): void
    {
        $message = (new SiteInstallationFailed($this->site))->toEmail($this->notificationChannel);

        $this->assertSame('error', $message->level);
        $this->assertSame(
            url('/servers/'.$this->site->server_id.'/sites/'.$this->site->id),
            $message->actionUrl
        );
        $this->assertStringContainsString('retry the installation', $message->render());
    }

    public function test_deployment_completed_includes_commit_summary_and_author(): void
    {
        $deployment = Deployment::factory()->create([
            'site_id' => $this->site->id,
            'status' => DeploymentStatus::FINISHED,
            'commit_id' => 'abcdef1234567890',
            'commit_data' => [
                'name' => 'Jane Dev',
                'message' => "Fix login redirect bug\n\nlong body",
            ],
        ]);

        $html = (new DeploymentCompleted($deployment, $this->site))->toEmail($this->notificationChannel)->render();

        $this->assertStringContainsString('Fix login redirect bug', $html);
        $this->assertStringContainsString('abcdef1', $html);
        $this->assertStringContainsString('Jane Dev', $html);
    }

    public function test_backup_failed_links_to_backup_with_error_level(): void
    {
        $backup = Backup::factory()->create([
            'server_id' => $this->server->id,
            'storage_id' => StorageProvider::factory()->create()->id,
            'database_id' => Database::factory()->create(['server_id' => $this->server->id])->id,
        ]);

        $message = (new BackupFailed($backup))->toEmail($this->notificationChannel);

        $this->assertSame('error', $message->level);
        $this->assertSame(url('/servers/'.$this->server->id.'/backups/'.$backup->id), $message->actionUrl);
    }

    public function test_webhook_deployment_failed_links_to_logs_with_error_level(): void
    {
        $message = (new WebhookDeploymentFailed($this->site))->toEmail($this->notificationChannel);

        $this->assertSame('error', $message->level);
        $this->assertSame(url('/servers/'.$this->site->server_id.'/logs'), $message->actionUrl);
    }

    public function test_project_invitation_renders_accept_url(): void
    {
        $project = Project::factory()->create();

        $html = (new ProjectInvitation($project))->render();

        $this->assertStringContainsString(
            route('projects.invitations.accept', ['project' => $project]),
            $html
        );
        $this->assertStringContainsString('email/logo.png', $html);
    }
}
