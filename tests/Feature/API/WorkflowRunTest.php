<?php

use App\Actions\Workflow\RunWorkflow;
use App\Enums\UserRole;
use App\Enums\WorkflowRunStatus;
use App\Facades\SSH;
use App\Models\Project;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowRun;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
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

test('cannot access workflow run from different workflow', function () {
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

    $response = $this->getJson("/api/projects/{$this->project->id}/workflows/{$this->workflow->id}/runs/{$otherWorkflowRun->id}");

    $response->assertNotFound();
});

test('cannot access workflow run from different project', function () {
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

    $response = $this->getJson("/api/projects/{$this->project->id}/workflows/{$this->workflow->id}/runs/{$otherWorkflowRun->id}");

    $response->assertNotFound();
});

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
