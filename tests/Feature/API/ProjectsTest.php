<?php

namespace Tests\Feature\API;

use App\Models\Project;
use App\Web\Pages\Settings\Projects\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
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

        $project = Project::factory()->create();

        $this->user->projects()->attach($project);

        $this->json('GET', '/api/projects')
            ->assertSuccessful()
            ->assertJsonFragment([
                'name' => $project->name,
            ]);
    }

    public function test_delete_project(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        $project = Project::factory()->create();

        $this->user->projects()->attach($project);

        Livewire::test(Settings::class, [
            'project' => $project,
        ])
            ->callAction('delete')
            ->assertSuccessful();

        $this->assertDatabaseMissing('projects', [
            'id' => $project->id,
        ]);
    }

    public function test_edit_project(): void
    {
        Sanctum::actingAs($this->user, ['read', 'write']);

        $project = Project::factory()->create();

        $this->user->projects()->attach($project);

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
            ->assertJsonValidationErrorFor('project');

        $this->assertDatabaseHas('projects', [
            'id' => $this->user->currentProject->id,
        ]);
    }
}
