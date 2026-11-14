<?php

use App\Actions\Workflow\RunWorkflow;
use App\Enums\UserRole;
use App\Enums\WorkflowRunStatus;
use App\Events\SocketEvent;
use App\Facades\SSH;
use App\Jobs\Workflow\RunJob;
use App\Models\Project;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowRun;
use App\WorkflowActions\General\RunCommand;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    Http::fake();
    Event::fake([SocketEvent::class]);
    $this->user = User::factory()->create();
    $this->project = Project::factory()->create();
    $this->project->users()->create([
        'user_id' => $this->user->id,
        'role' => UserRole::OWNER,
    ]);
    $this->workflow = Workflow::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->project->id,
        'name' => 'Test Workflow',
    ]);
    $this->workflowRun = WorkflowRun::factory()->create([
        'workflow_id' => $this->workflow->id,
        'user_id' => $this->user->id,
        'status' => WorkflowRunStatus::COMPLETED,
        'current_node_label' => 'Test Node',
        'current_node_id' => 'node-1',
    ]);
});

test('can list workflow runs', function () {
    Sanctum::actingAs($this->user, ['read', 'write']);

    $response = $this->getJson("/api/projects/{$this->project->id}/workflows/{$this->workflow->id}/runs");

    $response->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'workflow_id',
                    'status',
                    'status_color',
                    'current_node_label',
                    'current_node_id',
                    'created_at',
                    'updated_at',
                ],
            ],
            'links',
            'meta',
        ]);

    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.id'))->toEqual($this->workflowRun->id);
});

test('can run workflow', function () {
    SSH::fake();
    Sanctum::actingAs($this->user, ['read', 'write']);

    // Create a workflow with proper nodes and edges
    $workflow = Workflow::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->project->id,
        'name' => 'Test Workflow',
        'payload' => [
            'nodes' => [
                [
                    'id' => 'node-1',
                    'data' => [
                        'action' => [
                            'label' => 'Deploy Application',
                            'handler' => 'App\\WorkflowActions\\Deploy\\DeployApplication',
                            'outputs' => [
                                'deployment_id' => 'The ID of the deployment',
                            ],
                            'inputs' => [
                                'branch' => 'main',
                            ],
                            'starting' => true,
                        ],
                    ],
                ],
            ],
            'edges' => [],
        ],
    ]);

    $mockRunWorkflow = Mockery::mock(RunWorkflow::class);
    $mockRunWorkflow->shouldReceive('run')
        ->once()
        ->with(Mockery::on(function ($user) {
            return $user instanceof User && $user->id === $this->user->id;
        }), Mockery::on(function ($wf) use ($workflow) {
            return $wf instanceof Workflow && $wf->id === $workflow->id;
        }), ['branch' => 'main'])
        ->andReturn($this->workflowRun);

    $this->app->instance(RunWorkflow::class, $mockRunWorkflow);

    $response = $this->postJson("/api/projects/{$this->project->id}/workflows/{$workflow->id}/runs", [
        'branch' => 'main',
    ]);

    $response->assertCreated()
        ->assertJsonStructure([
            'id',
            'workflow_id',
            'status',
            'status_color',
            'current_node_label',
            'current_node_id',
            'created_at',
            'updated_at',
        ]);

    expect($response->json('id'))->toEqual($this->workflowRun->id);
});

test('can get single workflow run', function () {
    Sanctum::actingAs($this->user, ['read', 'write']);

    $response = $this->getJson("/api/projects/{$this->project->id}/workflows/{$this->workflow->id}/runs/{$this->workflowRun->id}");

    $response->assertSuccessful()
        ->assertJsonStructure([
            'id',
            'workflow_id',
            'status',
            'status_color',
            'current_node_label',
            'current_node_id',
            'created_at',
            'updated_at',
        ]);

    expect($response->json('id'))->toEqual($this->workflowRun->id);
});

test('can get workflow run logs', function () {
    Sanctum::actingAs($this->user, ['read', 'write']);

    // Mock the log content
    Storage::fake('server-logs');
    $logContent = "Test log content\nWith multiple lines";
    $this->workflowRun->log($logContent);

    $response = $this->getJson("/api/projects/{$this->project->id}/workflows/{$this->workflow->id}/runs/{$this->workflowRun->id}/log");

    $response->assertSuccessful()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8');

    $this->assertStringContainsString('Test log content', $response->getContent());
});

test('returns empty log when no log file exists', function () {
    Sanctum::actingAs($this->user, ['read', 'write']);

    $response = $this->getJson("/api/projects/{$this->project->id}/workflows/{$this->workflow->id}/runs/{$this->workflowRun->id}/log");

    $response->assertSuccessful()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8');

    $this->assertStringContainsString("Log file doesn't exist or is empty!", $response->getContent());
});

test('cannot access workflow run from different workflow', function (string $suffix) {
    Sanctum::actingAs($this->user, ['read', 'write']);

    $otherWorkflow = Workflow::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $this->project->id,
        'name' => 'Other Workflow',
    ]);
    $otherWorkflowRun = WorkflowRun::factory()->create([
        'workflow_id' => $otherWorkflow->id,
        'user_id' => $this->user->id,
        'status' => WorkflowRunStatus::RUNNING,
        'verbose' => true,
    ]);

    $response = $this->getJson("/api/projects/{$this->project->id}/workflows/{$this->workflow->id}/runs/{$otherWorkflowRun->id}{$suffix}");

    $response->assertNotFound();
})->with(['', '/log']);

test('cannot access workflow run from different project', function (string $suffix) {
    Sanctum::actingAs($this->user, ['read', 'write']);

    $otherProject = Project::factory()->create();
    $otherProject->users()->create([
        'user_id' => $this->user->id,
        'role' => UserRole::OWNER,
    ]);
    $otherWorkflow = Workflow::factory()->create([
        'user_id' => $this->user->id,
        'project_id' => $otherProject->id,
    ]);
    $otherWorkflowRun = WorkflowRun::factory()->create([
        'workflow_id' => $otherWorkflow->id,
        'user_id' => $this->user->id,
        'status' => WorkflowRunStatus::RUNNING,
        'verbose' => true,
    ]);

    $response = $this->getJson("/api/projects/{$this->project->id}/workflows/{$this->workflow->id}/runs/{$otherWorkflowRun->id}{$suffix}");

    $response->assertNotFound();
})->with(['', '/log']);

test('cannot access workflow run from different user', function () {
    Sanctum::actingAs($this->user, ['read', 'write']);

    $otherUser = User::factory()->create();
    $otherProject = Project::factory()->create();
    $otherProject->users()->create([
        'user_id' => $otherUser->id,
        'role' => UserRole::OWNER,
    ]);
    $otherWorkflow = Workflow::factory()->create([
        'user_id' => $otherUser->id,
        'project_id' => $otherProject->id,
    ]);
    $otherWorkflowRun = WorkflowRun::factory()->create([
        'workflow_id' => $otherWorkflow->id,
        'user_id' => $otherUser->id,
        'status' => WorkflowRunStatus::RUNNING,
        'verbose' => true,
    ]);

    $response = $this->getJson("/api/projects/{$otherProject->id}/workflows/{$otherWorkflow->id}/runs/{$otherWorkflowRun->id}");

    $response->assertForbidden();
});

test('cannot access nonexistent project', function () {
    Sanctum::actingAs($this->user, ['read', 'write']);

    $response = $this->getJson("/api/projects/999/workflows/{$this->workflow->id}/runs");

    $response->assertNotFound();
});

test('cannot access nonexistent workflow', function () {
    Sanctum::actingAs($this->user, ['read', 'write']);

    $response = $this->getJson("/api/projects/{$this->project->id}/workflows/999/runs");

    $response->assertNotFound();
});

test('cannot access nonexistent workflow run', function () {
    Sanctum::actingAs($this->user, ['read', 'write']);

    $response = $this->getJson("/api/projects/{$this->project->id}/workflows/{$this->workflow->id}/runs/999");

    $response->assertNotFound();
});

test('requires authentication', function () {
    // Create a fresh test case without authentication
    $this->refreshDatabase();

    $user = User::factory()->create();
    $project = Project::factory()->create();
    $project->users()->create([
        'user_id' => $user->id,
        'role' => UserRole::OWNER,
    ]);
    $workflow = Workflow::factory()->create([
        'user_id' => $user->id,
        'project_id' => $project->id,
    ]);

    $response = $this->getJson("/api/projects/{$project->id}/workflows/{$workflow->id}/runs");

    $response->assertUnauthorized();
});

test('requires read ability for listing', function () {
    Sanctum::actingAs($this->user, ['write']);

    $response = $this->getJson("/api/projects/{$this->project->id}/workflows/{$this->workflow->id}/runs");

    $response->assertForbidden();
});

test('requires write ability for running', function () {
    Sanctum::actingAs($this->user, ['read']);

    $response = $this->postJson("/api/projects/{$this->project->id}/workflows/{$this->workflow->id}/runs", [
        'branch' => 'main',
    ]);

    $response->assertForbidden();
});

test('pagination works correctly', function () {
    Sanctum::actingAs($this->user, ['read', 'write']);

    // Create additional workflow runs
    WorkflowRun::factory()->count(30)->create([
        'workflow_id' => $this->workflow->id,
        'user_id' => $this->user->id,
        'status' => WorkflowRunStatus::RUNNING,
        'verbose' => true,
    ]);

    $response = $this->getJson("/api/projects/{$this->project->id}/workflows/{$this->workflow->id}/runs");

    $response->assertSuccessful()
        ->assertJsonStructure([
            'data',
            'links' => [
                'first',
                'prev',
                'next',
            ],
            'meta' => [
                'current_page',
                'from',
                'per_page',
                'to',
            ],
        ]);

    // Should have pagination links since we have more than 25 workflow runs
    expect($response->json('links.next'))->not->toBeNull();
});

test('serialized workflow jobs enforce the initiating bearer token on resolved resources', function (string $scope, bool $allowed, bool $invalidToken) {
    SSH::fake();
    Storage::fake('server-logs');
    Queue::fake([RunJob::class]);
    $targetProject = $this->server->project;
    if ($scope !== 'non-member') {
        $targetProject->users()->create(['user_id' => $this->user->id, 'role' => UserRole::OWNER]);
    }
    $abilities = ['read', 'write', 'project:'.$this->project->id];
    if ($scope !== 'excluded') {
        $abilities[] = 'project:'.$targetProject->id;
    }
    if (in_array($scope, ['unrestricted', 'non-member'], true)) {
        $abilities = ['read', 'write'];
    }
    $token = $this->user->createToken('workflow', $abilities);
    $this->workflow->update(['payload' => [
        'nodes' => [[
            'id' => 'command',
            'data' => ['action' => [
                'label' => 'Run command',
                'handler' => RunCommand::class,
                'starting' => true,
                'inputs' => [],
                'outputs' => [],
            ]],
        ]],
        'edges' => [],
    ]]);

    $response = $this->withToken($token->plainTextToken)
        ->postJson("/api/projects/{$this->project->id}/workflows/{$this->workflow->id}/runs", [
            'inputs' => [
                'target_server_id' => $this->server->id,
                'server_id' => '{{target_server_id}}',
                'command' => 'echo workflow-token-scope',
                'user' => null,
            ],
        ])->assertCreated();

    Queue::assertPushed(RunJob::class);
    $serialized = serialize(Queue::pushed(RunJob::class)->first());
    expect($serialized)->not->toContain($token->plainTextToken)
        ->not->toContain($token->accessToken->token);

    match ($scope) {
        'revoked' => $token->accessToken->delete(),
        'expired' => $token->accessToken->forceFill(['expires_at' => now()->subMinute()])->save(),
        'lifetime-expired' => $token->accessToken->forceFill(['created_at' => now()->subHours(2)])->save(),
        'mismatched' => $token->accessToken->forceFill(['tokenable_id' => $this->server->user_id])->save(),
        'narrowed' => $token->accessToken->forceFill(['abilities' => ['write', 'project:'.$this->project->id]])->save(),
        'workflow-excluded' => $token->accessToken->forceFill(['abilities' => ['write', 'project:'.$targetProject->id]])->save(),
        'read-only' => $token->accessToken->forceFill(['abilities' => ['read']])->save(),
        default => null,
    };
    if ($scope === 'lifetime-expired') {
        config()->set('sanctum.expiration', 60);
    }
    $job = unserialize($serialized);
    $job->withFakeQueueInteractions()->handle();

    if ($allowed) {
        SSH::assertExecutedContains('echo workflow-token-scope');
    } else {
        SSH::assertNotExecutedContains('echo workflow-token-scope');
    }
    if ($invalidToken) {
        $job->assertFailedWith(AuthorizationException::class);
    } else {
        $job->assertNotFailed();
        $this->assertDatabaseHas('workflow_runs', [
            'id' => $response->json('id'),
            'status' => WorkflowRunStatus::COMPLETED->value,
        ]);
    }
    expect((new ReflectionProperty($job, 'user'))->getValue($job)->currentAccessToken())->toBeNull();
    $run = WorkflowRun::query()->findOrFail($response->json('id'));
    expect($run->getLogContent())->not->toContain($token->plainTextToken)
        ->not->toContain($token->accessToken->token);
})->with([
    'excluded project' => ['excluded', false, false],
    'both projects scoped' => ['allowed', true, false],
    'unrestricted token' => ['unrestricted', true, false],
    'non-member' => ['non-member', false, false],
    'revoked token' => ['revoked', false, true],
    'expired token' => ['expired', false, true],
    'expired token lifetime' => ['lifetime-expired', false, true],
    'changed token owner' => ['mismatched', false, true],
    'narrowed token scope' => ['narrowed', false, false],
    'workflow removed from scope' => ['workflow-excluded', false, true],
    'removed write ability' => ['read-only', false, true],
]);
