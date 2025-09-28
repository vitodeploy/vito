<?php

namespace Tests\Feature\API;

use App\Enums\UserRole;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProjectsTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_project(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        $this->json('POST', '/api/projects', [
            'name' => 'test',
        ])
            ->assertSuccessful();

        $this->assertDatabaseHas('projects', [
            'name' => 'test',
        ]);
    }

    public function test_see_projects_list(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        /** @var Project $project */
        $project = Project::factory()->create();
        $project->users()->create([
            'user_id' => $this->user->id,
            'role' => UserRole::ADMIN,
        ]);

        $this->json('GET', '/api/projects')
            ->assertSuccessful()
            ->assertJsonFragment([
                'name' => $project->name,
            ]);
    }

    public function test_delete_project(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        /** @var Project $project */
        $project = Project::factory()->create();
        $project->users()->create([
            'user_id' => $this->user->id,
            'role' => UserRole::OWNER,
        ]);

        $this->json('DELETE', '/api/projects/'.$project->id)
            ->assertSuccessful();

        $this->assertDatabaseMissing('projects', [
            'id' => $project->id,
        ]);
    }

    public function test_edit_project(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        /** @var Project $project */
        $project = Project::factory()->create();
        $project->users()->create([
            'user_id' => $this->user->id,
            'role' => UserRole::ADMIN,
        ]);

        $this->json('PUT', "/api/projects/{$project->id}", [
            'name' => 'new-name',
        ])
            ->assertSuccessful();

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'new-name',
        ]);
    }

    public function test_cannot_delete_last_project(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        $this->json('DELETE', "/api/projects/{$this->user->currentProject->id}")
            ->assertJsonValidationErrorFor('name');

        $this->assertDatabaseHas('projects', [
            'id' => $this->user->currentProject->id,
        ]);
    }
}
