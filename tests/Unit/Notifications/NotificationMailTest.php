<?php

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

uses(RefreshDatabase::class);

test('server installation succeed links to server with success level', function () {
    $message = (new ServerInstallationSucceed($this->server))->toEmail($this->notificationChannel);

    expect($message->level)->toBe('success');
    expect($message->actionUrl)->toBe(url('/servers/'.$this->server->id));
    $this->assertStringNotContainsString('/login', (string) $message->actionUrl);
});

test('failure notification uses error level', function () {
    $message = (new ServerInstallationFailed($this->server))->toEmail($this->notificationChannel);

    expect($message->level)->toBe('error');
});

test('rendered email contains vito branding', function () {
    $html = (new ServerInstallationSucceed($this->server))->toEmail($this->notificationChannel)->render();

    $this->assertStringContainsString('email/logo.png', $html);
    $this->assertStringContainsString('Sent by', $html);
});

test('site installation failed links to site with retry message', function () {
    $message = (new SiteInstallationFailed($this->site))->toEmail($this->notificationChannel);

    expect($message->level)->toBe('error');
    expect($message->actionUrl)->toBe(url('/servers/'.$this->site->server_id.'/sites/'.$this->site->id));
    $this->assertStringContainsString('retry the installation', $message->render());
});

test('deployment completed includes commit summary and author', function () {
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
});

test('backup failed links to backup with error level', function () {
    $backup = Backup::factory()->create([
        'server_id' => $this->server->id,
        'storage_id' => StorageProvider::factory()->create()->id,
        'database_id' => Database::factory()->create(['server_id' => $this->server->id])->id,
    ]);

    $message = (new BackupFailed($backup))->toEmail($this->notificationChannel);

    expect($message->level)->toBe('error');
    expect($message->actionUrl)->toBe(url('/servers/'.$this->server->id.'/backups/'.$backup->id));
});

test('webhook deployment failed links to logs with error level', function () {
    $message = (new WebhookDeploymentFailed($this->site))->toEmail($this->notificationChannel);

    expect($message->level)->toBe('error');
    expect($message->actionUrl)->toBe(url('/servers/'.$this->site->server_id.'/logs'));
});

test('project invitation renders accept url', function () {
    $project = Project::factory()->create();

    $html = (new ProjectInvitation($project))->render();

    $this->assertStringContainsString(
        route('projects.invitations.accept', ['project' => $project]),
        $html
    );
    $this->assertStringContainsString('email/logo.png', $html);
});
