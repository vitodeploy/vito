<?php

namespace Tests\Feature\API;

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
use Mockery;
use Tests\TestCase;

class WorkflowRunTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Project $project;

    protected Workflow $workflow;

    protected WorkflowRun $workflowRun;

    protected function setUp(): void
    {
        parent::setUp();

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

    }

    public function test_can_list_workflow_runs(): void
    {
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

        $this->assertCount(1, $response->json('data'));
        $this->assertEquals($this->workflowRun->id, $response->json('data.0.id'));
    }

    public function test_can_run_workflow(): void
    {
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

        $this->assertEquals($this->workflowRun->id, $response->json('id'));
    }

    public function test_can_get_single_workflow_run(): void
    {
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

        $this->assertEquals($this->workflowRun->id, $response->json('id'));
    }

    public function test_can_get_workflow_run_logs(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        // Mock the log content
        Storage::fake('server-logs');
        $logContent = "Test log content\nWith multiple lines";
        $this->workflowRun->log($logContent);

        $response = $this->getJson("/api/projects/{$this->project->id}/workflows/{$this->workflow->id}/runs/{$this->workflowRun->id}/log");

        $response->assertSuccessful()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8');

        $this->assertStringContainsString('Test log content', $response->getContent());
    }

    public function test_returns_empty_log_when_no_log_file_exists(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        $response = $this->getJson("/api/projects/{$this->project->id}/workflows/{$this->workflow->id}/runs/{$this->workflowRun->id}/log");

        $response->assertSuccessful()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8');

        $this->assertStringContainsString("Log file doesn't exist or is empty!", $response->getContent());
    }

    public function test_cannot_access_workflow_run_from_different_workflow(): void
    {
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
    }

    public function test_cannot_access_workflow_run_from_different_project(): void
    {
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
    }

    public function test_cannot_access_workflow_run_from_different_user(): void
    {
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
    }

    public function test_cannot_access_nonexistent_project(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        $response = $this->getJson("/api/projects/999/workflows/{$this->workflow->id}/runs");

        $response->assertNotFound();
    }

    public function test_cannot_access_nonexistent_workflow(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        $response = $this->getJson("/api/projects/{$this->project->id}/workflows/999/runs");

        $response->assertNotFound();
    }

    public function test_cannot_access_nonexistent_workflow_run(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        $response = $this->getJson("/api/projects/{$this->project->id}/workflows/{$this->workflow->id}/runs/999");

        $response->assertNotFound();
    }

    public function test_requires_authentication(): void
    {
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
    }

    public function test_requires_read_ability_for_listing(): void
    {
        Sanctum::actingAs($this->user, ['write']);

        $response = $this->getJson("/api/projects/{$this->project->id}/workflows/{$this->workflow->id}/runs");

        $response->assertForbidden();
    }

    public function test_requires_write_ability_for_running(): void
    {
        Sanctum::actingAs($this->user, ['read']);

        $response = $this->postJson("/api/projects/{$this->project->id}/workflows/{$this->workflow->id}/runs", [
            'branch' => 'main',
        ]);

        $response->assertForbidden();
    }

    public function test_pagination_works_correctly(): void
    {
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
        $this->assertNotNull($response->json('links.next'));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
