<?php

use App\Enums\SiteStatus;
use App\Exceptions\FailedToDeployGitKey;
use App\Facades\SSH;
use App\Jobs\Site\CreateJob;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

test('retry failed site resets state and dispatches create job', function () {
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
    expect($site->status)->toEqual(SiteStatus::INSTALLING);
    expect($site->last_error)->toBeNull();
    expect($site->progress_step)->toBeNull();
    expect($site->progress)->toEqual(0);

    Queue::assertPushedOn('ssh', CreateJob::class);
});

test('retry rejects site that is not failed', function () {
    $this->actingAs($this->user);

    $site = Site::factory()->create([
        'server_id' => $this->server->id,
        'status' => SiteStatus::READY,
    ]);

    $this->post(route('sites.retry', ['server' => $this->server, 'site' => $site]))
        ->assertSessionHasErrors(['status']);

    $site->refresh();
    expect($site->status)->toEqual(SiteStatus::READY);
});

test('create job failed populates safe last error and preserves progress step', function () {
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
    expect($site->status)->toEqual(SiteStatus::INSTALLATION_FAILED);
    expect($site->last_error)->not->toBeNull();
    $this->assertStringNotContainsString('raw provider response', $site->last_error);
    $this->assertStringContainsString('Installation failed', $site->last_error);
    expect($site->progress_step)->toEqual('cloning-repository');

    $this->assertDatabaseHas('server_logs', [
        'server_id' => $this->server->id,
        'site_id' => $site->id,
        'type' => 'site-installation-failed',
    ]);
});

test('create job failed uses friendly message for known exceptions', function () {
    Notification::fake();
    SSH::fake();

    $site = Site::factory()->create([
        'server_id' => $this->server->id,
        'status' => SiteStatus::INSTALLING,
    ]);

    $job = new CreateJob($site);
    $job->failed(new FailedToDeployGitKey('GitHub returned full key payload {"key": "ssh-rsa AAAA..."}'));

    $site->refresh();
    expect($site->last_error)->not->toBeNull();
    $this->assertStringNotContainsString('ssh-rsa', $site->last_error);
    $this->assertStringNotContainsString('AAAA', $site->last_error);
    $this->assertStringContainsString('deploy key', $site->last_error);
});
