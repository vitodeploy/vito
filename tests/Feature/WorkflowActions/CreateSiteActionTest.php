<?php

use App\Enums\UserRole;
use App\Facades\SSH;
use App\Models\Project;
use App\Models\Server;
use App\Models\User;
use App\Models\Workflow;
use App\WorkflowActions\Site\CreatePHPBlankSite;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('create site action fails with foreign server', function () {
    SSH::fake();

    $otherUser = User::factory()->create();
    $otherProject = Project::factory()->create();
    $otherProject->users()->create([
        'user_id' => $otherUser->id,
        'role' => UserRole::OWNER,
    ]);
    $otherServer = Server::factory()->create([
        'user_id' => $otherUser->id,
        'project_id' => $otherProject->id,
    ]);

    $workflow = Workflow::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->user->current_project_id,
    ]);

    $action = new CreatePHPBlankSite($this->user, $workflow);

    $this->expectException(AuthorizationException::class);

    $action->run([
        'server_id' => $otherServer->id,
        'type' => 'php-blank',
        'domain' => 'cross-project.example.com',
        'php_version' => '8.2',
        'web_directory' => 'public',
    ]);
});

test('create site action succeeds with own server', function () {
    SSH::fake();

    $workflow = Workflow::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->user->current_project_id,
    ]);

    $action = new CreatePHPBlankSite($this->user, $workflow);

    $result = $action->run([
        'server_id' => $this->server->id,
        'type' => 'php-blank',
        'domain' => 'my-site.example.com',
        'user' => 'mysiteuser',
        'php_version' => '8.2',
        'web_directory' => 'public',
    ]);

    expect($result)->toHaveKey('site_id');
    expect($result['site_domain'])->toEqual('my-site.example.com');
});
