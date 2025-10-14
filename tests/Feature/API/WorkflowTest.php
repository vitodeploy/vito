<?php

namespace Tests\Feature\API;

use App\Enums\UserRole;
use App\Models\Project;
use App\Models\User;
use App\Models\Workflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Project $project;

    protected Workflow $workflow;

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
    }

    public function test_can_list_workflows(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        $response = $this->getJson("/api/projects/{$this->project->id}/workflows");

        $response->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'project_id',
                        'user_id',
                        'name',
                        'nodes',
                        'edges',
                        'run_inputs',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'links',
                'meta',
            ]);

        $this->assertCount(1, $response->json('data'));
        $this->assertEquals($this->workflow->id, $response->json('data.0.id'));
    }

    public function test_can_get_single_workflow(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        $response = $this->getJson("/api/projects/{$this->project->id}/workflows/{$this->workflow->id}");

        $response->assertSuccessful()
            ->assertJsonStructure([
                'id',
                'project_id',
                'user_id',
                'name',
                'nodes',
                'edges',
                'run_inputs',
                'created_at',
                'updated_at',
            ]);

        $this->assertEquals($this->workflow->id, $response->json('id'));
        $this->assertEquals('Test Workflow', $response->json('name'));
    }

    public function test_can_delete_workflow(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        $response = $this->deleteJson("/api/projects/{$this->project->id}/workflows/{$this->workflow->id}");

        $response->assertNoContent();

        $this->assertSoftDeleted('workflows', [
            'id' => $this->workflow->id,
        ]);
    }

    public function test_cannot_access_workflow_from_different_project(): void
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

        $response = $this->getJson("/api/projects/{$this->project->id}/workflows/{$otherWorkflow->id}");

        $response->assertNotFound();
    }

    public function test_cannot_access_workflow_from_different_user(): void
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

        $response = $this->getJson("/api/projects/{$otherProject->id}/workflows/{$otherWorkflow->id}");

        $response->assertForbidden();
    }

    public function test_cannot_access_nonexistent_project(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        $response = $this->getJson("/api/projects/999/workflows/{$this->workflow->id}");

        $response->assertNotFound();
    }

    public function test_cannot_access_nonexistent_workflow(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        $response = $this->getJson("/api/projects/{$this->project->id}/workflows/999");

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

        $response = $this->getJson("/api/projects/{$project->id}/workflows");

        $response->assertUnauthorized();
    }

    public function test_requires_read_ability_for_listing(): void
    {
        Sanctum::actingAs($this->user, ['write']);

        $response = $this->getJson("/api/projects/{$this->project->id}/workflows");

        $response->assertForbidden();
    }

    public function test_requires_write_ability_for_deleting(): void
    {
        Sanctum::actingAs($this->user, ['read']);

        $response = $this->deleteJson("/api/projects/{$this->project->id}/workflows/{$this->workflow->id}");

        $response->assertForbidden();
    }

    public function test_pagination_works_correctly(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        // Create additional workflows
        Workflow::factory()->count(30)->create([
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
        ]);

        $response = $this->getJson("/api/projects/{$this->project->id}/workflows");

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

        $this->assertNotNull($response->json('links.next'));
    }
}
