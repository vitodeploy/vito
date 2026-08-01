<?php

use App\Enums\UserRole;
use App\Models\Project;
use App\Models\User;
use App\Models\Workflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
});

test('can list workflows', function () {
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

    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.id'))->toEqual($this->workflow->id);
});

test('can get single workflow', function () {
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

    expect($response->json('id'))->toEqual($this->workflow->id);
    expect($response->json('name'))->toEqual('Test Workflow');
});

test('can delete workflow', function () {
    Sanctum::actingAs($this->user, ['read', 'write']);

    $response = $this->deleteJson("/api/projects/{$this->project->id}/workflows/{$this->workflow->id}");

    $response->assertNoContent();

    $this->assertSoftDeleted('workflows', [
        'id' => $this->workflow->id,
    ]);
});

test('cannot access workflow from different project', function () {
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
});

test('cannot access workflow from different user', function () {
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
});

test('cannot access nonexistent project', function () {
    Sanctum::actingAs($this->user, ['read', 'write']);

    $response = $this->getJson("/api/projects/999/workflows/{$this->workflow->id}");

    $response->assertNotFound();
});

test('cannot access nonexistent workflow', function () {
    Sanctum::actingAs($this->user, ['read', 'write']);

    $response = $this->getJson("/api/projects/{$this->project->id}/workflows/999");

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

    $response = $this->getJson("/api/projects/{$project->id}/workflows");

    $response->assertUnauthorized();
});

test('requires read ability for listing', function () {
    Sanctum::actingAs($this->user, ['write']);

    $response = $this->getJson("/api/projects/{$this->project->id}/workflows");

    $response->assertForbidden();
});

test('requires write ability for deleting', function () {
    Sanctum::actingAs($this->user, ['read']);

    $response = $this->deleteJson("/api/projects/{$this->project->id}/workflows/{$this->workflow->id}");

    $response->assertForbidden();
});

test('pagination works correctly', function () {
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

    expect($response->json('links.next'))->not->toBeNull();
});
